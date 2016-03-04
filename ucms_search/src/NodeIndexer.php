<?php

namespace MakinaCorpus\Ucms\Search;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\node\NodeInterface;

use Elasticsearch\Client;

/**
 * @todo unit test me
 */
class NodeIndexer implements NodeIndexerInterface
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
     * @var EntityStorageInterface
     */
    private $nodeStorage;

    /**
     * @var boolean
     */
    private $preventBulkUsage = false;

    /**
     * Temporary queue of node identifiers which need to be marked as changed
     * upon the request termination
     *
     * @var int[]
     */
    private $nodeQueue = [];

    /**
     * Index to work with
     *
     * @var string
     */
    private $index;

    /**
     * @var string
     */
    private $indexRealname;

    /**
     * Default constructor
     *
     * @param string $index
     * @param Client $client
     * @param \DatabaseConnection $db
     * @param EntityManager $entityManager
     * @param ModuleHandlerInterface $moduleHandler
     * @param boolean $preventBulkUsage
     */
    public function __construct(
        $index,
        Client $client,
        \DatabaseConnection $db,
        EntityManager $entityManager,
        ModuleHandlerInterface $moduleHandler,
        $indexRealname = null,
        $preventBulkUsage = false)
    {
        $this->index = $index;
        $this->client = $client;
        $this->db = $db;
        $this->nodeStorage = $entityManager->getStorage('node');
        $this->moduleHandler = $moduleHandler;
        $this->indexRealname = ($indexRealname ? $indexRealname : $index);
        $this->preventBulkUsage = $preventBulkUsage;
    }

    /**
     * {@inheritdoc}
     */
    public function enqueue(array $nodes)
    {
        foreach ($nodes as $node) {
            $this->nodeQueue[$node->nid] = $node->nid;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function dequeue()
    {
        if (empty($this->nodeQueue)) {
            return;
        }

        $this->bulkMarkForReindex($this->nodeQueue);

        $this->nodeQueue = [];
    }

    /**
     * {@inheritdoc}
     */
    public function bulkDequeue($limit = null)
    {
        if (!$limit) {
            $limit = variable_get('ucms_search_cron_limit', UCMS_SEARCH_CRON_INDEX_LIMIT);
        }

        $nodeIdList = $this
            ->db
            ->select('ucms_search_status', 's')
            ->fields('s', ['nid'])
            ->condition('s.needs_reindex', 1)
            ->condition('s.index_key', $this->index)
            ->range(0, $limit)
            ->execute()
            ->fetchCol()
        ;

        // Preload all nodes for performance
        $nodes = $this->nodeStorage->loadMultiple($nodeIdList);
        if (!$nodes) {
            return 0;
        }
        $count = count($nodes);

        $this->bulkUpsert($nodes, true, false);

        $toBeDeleted = array_diff(array_keys($nodes), $nodeIdList);
        foreach ($toBeDeleted as $nid) {
            $this->delete($nid);
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function bulkMarkForReindex($nidList = null)
    {
        $deleteQuery = $this
            ->db
            ->delete('ucms_search_status')
            ->condition('index_key', $this->index)
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

        $query->addExpression(':index', 'index_key', [':index' => $this->index]);
        $query->addExpression(1, 'needs_reindex');

        $this->moduleHandler->invokeAll('ucms_search_index_reindex', [$this->index, $query]);

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
     * {@inheritdoc}
     */
    protected function nodeToFulltext(NodeInterface $node, $field_name)
    {
        if (field_get_items('node', $node, $field_name)) {
            $build = field_view_field('node', $node, $field_name, 'full');

            return drupal_render($build);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function nodeExtractTagIdList(NodeInterface $node, $field_name)
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

    protected function nodeExtractGrants(NodeInterface $node)
    {
        // @todo Drupal is stupid, fix this
        $grants = module_invoke_all('node_access_records', $node);
        drupal_alter('node_access_records', $grants, $node);
        if (empty($grants) && !empty($node->status)) {
            $grants[] = ['realm' => 'all', 'gid' => 0, 'grant_view' => 1];
        }

        // Rewrite grants for elastic mapping usage
        foreach ($grants as $index => $grant) {
            if ($grant['grant_view']) {
                $grants[$index] = $grant['realm'] . ':' . $grant['gid'];
            } else {
                unset($grants[$index]);
            }
        }

        return array_values($grants);
    }

    /**
     * {@inheritdoc}
     */
    protected function nodeProcessfield(NodeInterface $node)
    {
        $created  = null;
        $changed  = null;

        try {
            $created = new \DateTime('@' . $node->getCreatedTime());
        } catch (Exception $e) {}
        try {
            $changed = new \DateTime('@' . $node->getChangedTime());
        } catch (Exception $e) {}

        // @todo Use field mapping from the index definition.
        // @todo Allow via usync in param definition to set different field names
        //   between the elastic index and the node field/properties.
        // @todo Note the right place for this todo but allow usync definition to
        //   also include basic matching rules whenever possible.

        return [
            'title'       => $node->getTitle(),
            'id'          => $node->id(),
            'owner'       => $node->getOwnerId(),
            'created'     => $created ? $created->format(\DateTime::ISO8601) : null,
            'updated'     => $changed ? $changed->format(\DateTime::ISO8601) : null,
            'type'        => $node->getType(),
            'body'        => strip_tags($this->nodeToFulltext($node, 'body')),
            'status'      => (int)$node->isPublished(),
            'tags'        => $this->nodeExtractTagIdList($node, 'tags'),
            'is_starred'  => (bool)$node->is_starred,
            'is_flagged'  => (bool)$node->is_flagged,
            'is_global'   => (bool)$node->is_global,
            'is_group'    => (bool)$node->is_group,
            'is_locked'   => !(bool)$node->is_clonable,
            'node_access' => $this->nodeExtractGrants($node),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function delete(NodeInterface $node)
    {
        $this
            ->client
            ->delete([
                'index' => $this->indexRealname,
                'id'    => $node->id(),
                'type'  => 'node',
            ])
        ;

        $this
            ->db
            ->delete('ucms_search_status')
            ->condition('nid', $node->id())
            ->condition('index_key', $this->index)
            ->execute()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function matches(NodeInterface $node)
    {
        foreach ($this->moduleHandler->getImplementations(self::HOOK_NODE_INDEX) as $module) {
            if ($this->moduleHandler->invoke($module, self::HOOK_NODE_INDEX, [$this->index, $node])) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function bulkUpsert($nodeList, $force = false, $refresh = false)
    {
        if (empty($nodeList)) {
            return;
        }

        if ($this->preventBulkUsage) {
            foreach ($nodeList as $node) {
                $this->upsert($node);
            }
            return;
        }

        $params   = [];
        $nidList  = [];

        foreach ($nodeList as $key => $node) {

            if (!$force && !$this->matches($node)) {
                unset($nodeList[$key]);
            }

            $params['body'][] = [
                'index' => [
                    '_index'   => $this->indexRealname,
                    '_id'      => $node->id(),
                    '_type'    => 'node',
                    // @todo Refresh could be global.
                    // '_refresh' => (bool)$refresh,
                ],
            ];

            $params['body'][] = $this->nodeProcessfield($node);

            $nidList[] = $node->id();
        }

        $this->client->bulk($params);

        $this
            ->db
            ->update('ucms_search_status')
            ->fields(['needs_reindex' => 0])
            ->condition('nid', $nidList)
            ->condition('index_key', $this->index)
            ->execute()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function upsert(NodeInterface $node, $force = false, $refresh = false)
    {
        if (!$force && !$this->matches($node)) {
            return false;
        }

        $this
            ->client
            ->index([
                'index'   => $this->indexRealname,
                'id'      => $node->id(),
                'type'    => 'node',
                'refresh' => (bool)$refresh,
                'body'    => $this->nodeProcessfield($node),
            ])
        ;

        $this
            ->db
            ->update('ucms_search_status')
            ->fields(['needs_reindex' => 0])
            ->condition('nid', $node->id())
            ->condition('index_key', $this->index)
            ->execute()
        ;

        return true;
    }
}
