<?php

namespace MakinaCorpus\Ucms\Search;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Extension\ModuleHandlerInterface;

use Elasticsearch\Client;

class NodeIndexer
{
    /**
     * Node(s) (is|are) being indexed hook
     */
    const HOOK_NODE_INDEX = 'ucms_search_index_node';

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
     * @var \DrupalEntityControllerInterface
     */
    private $nodeController;

    /**
     * Default constructor
     *
     * @param Client $client
     * @param \DatabaseConnection $db
     * @param EntityManager $entityManager
     * @param ModuleHandlerInterface $moduleHandler
     */
    public function __construct(
        Client $client,
        \DatabaseConnection $db,
        EntityManager $entityManager,
        ModuleHandlerInterface $moduleHandler)
    {
        $this->client = $client;
        $this->db = $db;
        $this->nodeController = $entityManager->getStorage('node');
        $this->moduleHandler = $moduleHandler;
    }

    /**
     * Dequeue and index items
     *
     * @param string $index
     * @param int $limit
     */
    public function bulkDequeue($index, $limit = null)
    {
        if (!$limit) {
            $limit = variable_get('ucms_search_cron_limit', UCMS_SEARCH_CRON_INDEX_LIMIT);
        }

        $nodeIdList = $this
            ->db
            ->select('ucms_search_status', 's')
            ->fields('s', ['nid'])
            ->condition('s.needs_reindex', 1)
            ->condition('s.index_key', $index)
            ->range(0, $limit)
            ->execute()
            ->fetchCol()
        ;

        // Preload all nodes for performance
        $nodes = $this->nodeController->load($nodeIdList);
        if (!$nodes) {
            return 0;
        }
        $count = count($nodes);

        $this->bulkUpsert($index, $nodes, true, false);

        $toBeDeleted = array_diff(array_keys($nodes), $nodeIdList);
        foreach ($toBeDeleted as $nid) {
            $this->delete($index, $nid);
        }

        return $count;
    }

    /**
     * Mark all content for reindexing in an index.
     *
     * @param string $index
     * @param int|int[] $nidList
     *   List of node identifiers to reindex or delete
     */
    public function bulkMarkForReindex($index, $nidList = null)
    {
        $deleteQuery = $this
            ->db
            ->delete('ucms_search_status')
            ->condition('index_key', $index)
        ;

        if (null !== $nidList) {
            $deleteQuery->condition('nid', $nidList);
        }
        $deleteQuery->execute();

        $query = $this
            ->db
            ->select('node', 'n')
            ->fields('n', ['nid'])
        ;

        $query->addExpression(':index', 'index_key', [':index' => $index]);
        $query->addExpression(1, 'needs_reindex');

        $this->moduleHandler->invokeAll('ucms_search_index_reindex', [$index, $query]);

        if (null !== $nidList) {
            $query->condition('n.nid', $nidList);
        }

        $this
            ->db
            ->insert('ucms_search_status')
            ->from($query)
            ->execute()
        ;
    }

    /**
     * Extract textual data from content
     *
     * @param stdClass $node
     * @param string $field_name
     *
     * @return string
     */
    protected function nodeToFulltext($node, $field_name)
    {
        if (field_get_items('node', $node, $field_name)) {
            $build = field_view_field('node', $node, $field_name, 'full');

            return drupal_render($build);
        }
    }

    /**
     * Extract term identifiers from field
     *
     * @param stdClass $node
     * @param string $field_name
     *
     * @return int[]
     */
    protected function nodeExtractTagIdList($node, $field_name)
    {
        $ret = [];

        if ($items = field_get_items('node', $node, $field_name)) {
            foreach ($items as $item) {
                if (isset($item['tid'])) {
                    $ret[] = (int)$item['tid'];
                }
            }
        }

        return $ret;
    }

    /**
     * Process all fields of the given node and return an elastic friendly array
     *
     * @param stdClass $node
     *
     * @return array
     */
    protected function nodeProcessfield($node)
    {
        $created  = null;
        $changed  = null;

        try {
            $created = new \DateTime('@' . $node->created);
        } catch (Exception $e) {}
        try {
            $changed = new \DateTime('@' . $node->changed);
        } catch (Exception $e) {}

        // @todo Use field mapping from the index definition.
        // @todo Allow via usync in param definition to set different field names
        //   between the elastic index and the node field/properties.
        // @todo Note the right place for this todo but allow usync definition to
        //   also include basic matching rules whenever possible.

        return [
            'title'   => $node->title,
            'id'      => $node->nid,
            'owner'   => $node->uid,
            'created' => $created ? $created->format(\DateTime::ISO8601) : null,
            'updated' => $changed ? $changed->format(\DateTime::ISO8601) : null,
            'type'    => $node->type,
            'body'    => strip_tags($this->nodeToFulltext($node, 'body')),
            'status'  => (int)$node->status,
            'tags'    => $this->nodeExtractTagIdList($node, 'tags'),
        ];
    }

    /**
     * Remove a single node from index
     *
     * @param string $index
     * @param int $nid
     */
    public function delete($index, $node)
    {
        $response = $this
            ->client
            ->delete([
                'index' => $index,
                'id'    => $node->nid,
                'type'  => 'node',
            ])
        ;

        if (UCMS_SEARCH_ELASTIC_DEBUG) {
            watchdog(__FUNCTION__, '<pre>' . print_r($response, true) . '</pre>', null, WATCHDOG_DEBUG);
        }

        $this
            ->db
            ->delete('ucms_search_status')
            ->condition('nid', $node->nid)
            ->condition('index_key', $index)
            ->execute()
        ;
    }

    /**
     * Tell if the given node matches the given index.
     *
     * @param string $index
     * @param stdClass $node
     *
     * @return boolean
     */
    public function matches($index, $node)
    {
        foreach ($this->moduleHandler->implementsHook(self::HOOK_NODE_INDEX) as $module) {
            if ($this->moduleHandler->invoke($module, self::HOOK_NODE_INDEX, [$index, $node])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Index or upsert given nodes using a bulk request
     *
     * Note that Elastic Search will be as fast if you choose to do an explicit
     * upsert or attempt to index a document with an already existing identifier,
     * reason why we actually choose to only send index operations, this way we
     * don't have to check ourselves if the document exists in the index or not.
     * Elastic Search index operation is actually an upsert operation.
     *
     * @param string $index
     *   Index identifier
     * @param stdClass[] $nodeList
     *   Node object
     * @param boolean $force
     *   Internal boolean, skip match test, this should never be used
     *   outside of this module
     * @param boolean $refresh
     *   Explicit refresh set to true for ElasticSearch, forcing the full shard
     *   to be in sync for the next search
     */
    public function bulkUpsert($index, $nodeList, $force = false, $refresh = false)
    {
        if (empty($nodeList)) {
            return;
        }

        $params   = [];
        $nidList  = [];

        foreach ($nodeList as $key => $node) {

            if (!$force && !$this->matches($index, $node)) {
                unset($nodeList[$key]);
            }

            $params['body'][] = [
                'index' => [
                    '_index'   => $index,
                    '_id'      => $node->nid,
                    '_type'    => 'node',
                    // @todo Refresh could be global.
                    // '_refresh' => (bool)$refresh,
                ],
            ];

            $params['body'][] = $this->nodeProcessfield($node);

            $nidList[] = $node->nid;
        }

        $response = $this->client->bulk($params);

        if (UCMS_SEARCH_ELASTIC_DEBUG) {
            watchdog(__FUNCTION__, '<pre>' . print_r($response, true) . '</pre>', null, WATCHDOG_DEBUG);
        }

        $this
            ->db
            ->update('ucms_search_status')
            ->fields(['needs_reindex' => 0])
            ->condition('nid', $nidList)
            ->condition('index_key', $index)
            ->execute()
        ;
    }

    /**
     * Index or upsert a single node
     *
     * Note that Elastic Search will be as fast if you choose to do an explicit
     * upsert or attempt to index a document with an already existing identifier,
     * reason why we actually choose to only send index operations, this way we
     * don't have to check ourselves if the document exists in the index or not.
     * Elastic Search index operation is actually an upsert operation.
     *
     * @param string $index
     *   Index identifier
     * @param stdClass $node
     *   Node object
     * @param boolean $force
     *   Internal boolean, skip match test, this should never be used
     *   outside of this module
     * @param boolean $refresh
     *   Explicit refresh set to true for ElasticSearch, forcing the full shard
     *   to be in sync for the next search
     *
     * @return boolean
     *   True if the index command has been sent or false if node was dropped
     */
    public function upsert($index, $node, $force = false, $refresh = false)
    {
        if (!$force && !$this->matches($index, $node)) {
            return false;
        }

        $response = $this
            ->client
            ->index([
                'index'   => $index,
                'id'      => $node->nid,
                'type'    => 'node',
                'refresh' => (bool)$refresh,
                'body'    => $this->nodeProcessfield($node),
            ])
        ;

        if (UCMS_SEARCH_ELASTIC_DEBUG) {
            watchdog(__FUNCTION__, '<pre>' . print_r($response, true) . '</pre>', null, WATCHDOG_DEBUG);
        }

        $this
            ->db
            ->update('ucms_search_status')
            ->fields(['needs_reindex' => 0])
            ->condition('nid', $node->nid)
            ->condition('index_key', $index)
            ->execute()
        ;

        return true;
    }
}
