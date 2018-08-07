<?php

namespace MakinaCorpus\Ucms\Search;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Extension\ModuleHandlerInterface;

use Elasticsearch\Client;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class IndexStorage
{
    /**
     * An index definition is being saved, and can be modified
     */
    const EVENT_INDEX_SAVE = 'ucms_search_index_save';

    /**
     * An index definition is being saved, and can be modified
     */
    const HOOK_DEF_SAVE = 'ucms_search_index_definition_save';

    /**
     * An index definition has been deleted
     */
    const HOOK_DEF_DELETE = 'ucms_search_index_definition_delete';

    /**
     * Index list cache entry name
     */
    const CID_LIST = 'ucms_search_index_list';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * The module handler.
     *
     * @var ModuleHandlerInterface
     */
    private $moduleHandler;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var boolean
     */
    private $preventBulkUsage = false;

    /**
     * @var string[]
     */
    private $indexAliasMap = [];

    /**
     * @var string[]
     */
    private $indexListCache = [];

    /**
     * var array[]
     */
    private $indexDefinitionCache = [];

    /**
     * @var CacheBackendInterface
     */
    private $cache;

    /**
     * @var NodeIndexerChain
     */
    private $nodeIndexerChain;

    /**
     * Default constructor
     *
     * @param Client $client
     * @param \DatabaseConnection $db
     * @param \DrupalCacheInterface $cache
     * @param ModuleHandlerInterface $moduleHandler
     * @param string[] $indexAliasMap
     *   Keys are logical indices names (those used as keys for the Drupal code)
     *   while values are the real indices names in Elastic Search.
     * @param boolean $preventBulkUsage
     *   If set to true, the indexers will be configured to avoid the ES bulk
     *   API, this is an ugly workaround due to a buggy environment we do use
     */
    public function __construct(
        Client $client,
        \DatabaseConnection $db,
        CacheBackendInterface $cache,
        EntityManager $entityManager,
        EventDispatcherInterface $eventDispatcher,
        ModuleHandlerInterface $moduleHandler,
        array $indexAliasMap = null,
        $preventBulkUsage = false)
    {
        $this->client = $client;
        $this->db = $db;
        $this->cache = $cache;
        $this->entityManager = $entityManager;
        $this->moduleHandler = $moduleHandler;
        $this->eventDispatcher = $eventDispatcher;
        if ($indexAliasMap) {
            $this->indexAliasMap = $indexAliasMap;
        }
        $this->preventBulkUsage = $preventBulkUsage;
    }

    /**
     * Get index real name for Elastic Search
     *
     * @param string $index
     *
     * @return string
     */
    public function getIndexRealname($index)
    {
        if (isset($this->indexAliasMap[$index])) {
            return $this->indexAliasMap[$index];
        }

        return $index;
    }

    /**
     * Get node indexer for the given index
     *
     * @param string $index
     *   If none is given, a passthru implementation that will work internally
     *   on all indices at once will be given
     *
     * @return NodeIndexerInterface
     */
    public function indexer($index = null)
    {
        if (empty($this->nodeIndexerChain)) {
            $list = [];
            foreach ($this->keys() as $existing) {
                $list[$existing] = new NodeIndexer(
                    $existing,
                    $this->client,
                    $this->db,
                    $this->entityManager,
                    $this->moduleHandler,
                    $this->eventDispatcher,
                    $this->getIndexRealname($existing),
                    $this->preventBulkUsage
                );
            }
            $this->nodeIndexerChain = new NodeIndexerChain($list);
        }

        if ($index) {
            return $this->nodeIndexerChain->getIndexer($index);
        }

        return $this->nodeIndexerChain;
    }

    /**
     * Create or update index definition
     *
     * @param string $index
     *   Index key
     * @param string $name
     *   Human readable title
     * @param array $param
     *   This must contain the exact replica of the 'body' key that will be sent
     *   to ElasticSearch indices()::create() array
     * @param boolean $force
     *   Force index to be refreshed
     */
    public function save($index, $name, $param, $force = false)
    {
        $updated  = false;
        $existing = $this->load($index);

        // Directly compare array structures, should be enough
        if (!$existing || $force || $param !== $existing) {
            // This was updated, then we really need to save it
            $updated = true;
        }

        // Adds 'node_access' table ACL system replica into elastic index
        // mapping so that we can query it later, this will only replicate
        // grants for the view operation, here is the process:
        //   - it identifies each (realm, gid) group for the node as a raw
        //     string such as "REALM:GID"
        //   - it stores a string term vector of those
        //   - of course, it only stores those with 1 as value for grant_view
        //   - in all queries, a nice alteration will be done to force it to
        //     have a filter query that does a term exact match in the vector
        //     for all user grants
        $param['mapping']['node']['properties']['node_access']['type'] = 'string';

        $this
            ->db
            ->merge('ucms_search_index')
            ->key(['index_key' => $index])
            ->fields(['name' => $name, 'data' => serialize($param)])
            ->execute()
        ;

        $this->clearDefinitionCache();
        $this->moduleHandler->invokeAll(self::HOOK_DEF_SAVE, [$index, $param, $updated, !$existing]);

        $this->indexer();
        $this->nodeIndexerChain->addIndexer(
            $index,
            new NodeIndexer(
                $index,
                $this->client,
                $this->db,
                $this->entityManager,
                $this->moduleHandler,
                $this->eventDispatcher,
                $this->getIndexRealname($index),
                $this->preventBulkUsage
            ));

        if ($updated) {
            $this->clear($index);
        }
    }

    /**
     * Does this index exists
     *
     * @param string $index
     *
     * @return boolean
     */
    public function exists($index)
    {
        $names = $this->names();

        return isset($names[$index]);
    }

    /**
     * Get indices machines names
     *
     * @return string[]
     *   Keys are indices machine names while values are human readables names
     */
    public function keys()
    {
        return array_keys($this->names());
    }

    /**
     * Get indices names
     *
     * @return string[]
     *   Keys are indices machine names while values are human readables names
     */
    public function names()
    {
        if ($this->indexListCache) {
            return $this->indexListCache;
        }

        // Attempt to have a zero SQL Drupal
        $doCache = variable_get('ucms_search_cache_list', true);

        if ($doCache && ($cached = $this->cache->get(self::CID_LIST))) {
            $this->indexListCache = $cached->data;
        } else {

            $this->indexListCache = $this
                ->db
                ->query(
                    "SELECT index_key, name FROM {ucms_search_index}"
                )
                ->fetchAllKeyed()
            ;

            if ($doCache) {
                $this->cache->set(self::CID_LIST, $this->indexListCache);
            }
        }

        return $this->indexListCache;
    }

    /**
     * Load an index definition
     *
     * This is uncached, never use it during normal runtime
     *
     * @param string $index
     *
     * @return array
     *   This must contain the exact replica of the 'body' key that will be sent
     *   to ElasticSearch indices()::create() array.
     */
    public function load($index)
    {
        // Minor optimization, avoid lookups for unknow indexes
        if (!$this->exists($index)) {
            return;
        }

        // Can be null if index does not exists
        if (array_key_exists($index, $this->indexDefinitionCache)) {
            return $this->indexDefinitionCache[$index];
        }

        $param = $this
            ->db
            ->query(
                "SELECT data FROM {ucms_search_index} WHERE index_key = :index",
                ['index' => $index]
            )
            ->fetchField()
        ;

        if ($param) {
          $param = unserialize($param);
        }

        return $this->indexDefinitionCache[$index] = $param;
    }

    /**
     * Delete an index definition
     *
     * @param string $index
     */
    public function delete($index)
    {
        $this
            ->db
            ->delete('ucms_search_index')
            ->condition('index_key', $index)
            ->execute()
        ;

        $this->indexer();
        $this->nodeIndexerChain->removeIndexer($index);

        $this->clearDefinitionCache();

        $this->moduleHandler->invokeAll(self::HOOK_DEF_DELETE, [$index]);

        $this->deleteInClient($index);
    }

    /**
     * Clear an index.
     *
     * @param string $index
     */
    public function clear($index)
    {
        $this->deleteInClient($index);
        $this->createInClient($index);

        $this->indexer();
        $this->nodeIndexerChain->getIndexer($index)->bulkMarkForReindex();
    }

    /**
     * Ensure index exists
     *
     * @param string $index
     */
    protected function createInClient($index)
    {
        $param = $this->load($index);

        if (!$param) {
            throw new \InvalidArgumentException(sprintf("'%s' index definition does not exist", $index));
        }

        $namespace      = $this->client->indices();
        $indexRealname  = $this->getIndexRealname($index);

        if (!$namespace->exists(['index' => $indexRealname])) {
            $namespace->create([
                'index' => $indexRealname,
                'body'  => $param,
            ]);
        }
    }

    /**
     * Delete an index on Elastic client without removing the Drupal definition
     *
     * @param string $index
     */
    protected function deleteInClient($index)
    {
        $namespace      = $this->client->indices();
        $indexRealname  = $this->getIndexRealname($index);

        if ($namespace->exists(['index' => $indexRealname])) {
            $namespace->delete(['index' => $indexRealname]);
        }
    }

    /**
     * Clear all index definition related cache
     */
    protected function clearDefinitionCache()
    {
        $this->indexDefinitionCache = [];
        $this->indexListCache = [];
        $this->cache->delete(self::CID_LIST);
    }
}
