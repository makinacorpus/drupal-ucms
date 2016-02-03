<?php

namespace MakinaCorpus\Ucms\Search;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Extension\ModuleHandlerInterface;

use Elasticsearch\Client;

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
     * @var \DrupalEntityControllerInterface
     */
    private $nodeController;

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
     * Default constructor
     *
     * @param string $index
     * @param Client $client
     * @param \DatabaseConnection $db
     * @param EntityManager $entityManager
     * @param ModuleHandlerInterface $moduleHandler
     */
    public function __construct(
        $index,
        Client $client,
        \DatabaseConnection $db,
        EntityManager $entityManager,
        ModuleHandlerInterface $moduleHandler)
    {
        $this->index = $index;
        $this->client = $client;
        $this->db = $db;
        $this->nodeController = $entityManager->getStorage('node');
        $this->moduleHandler = $moduleHandler;
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

        $this->bulkMarkForReindex($this->index, $this->nodeQueue);

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
        $nodes = $this->nodeController->load($nodeIdList);
        if (!$nodes) {
            return 0;
        }
        $count = count($nodes);

        $this->bulkUpsert($nodes, true, false);

        $toBeDeleted = array_diff(array_keys($nodes), $nodeIdList);
        foreach ($toBeDeleted as $nid) {
            $this->delete($this->index, $nid);
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
    protected function nodeToFulltext($node, $field_name)
    {
        if (field_get_items('node', $node, $field_name)) {
            $build = field_view_field('node', $node, $field_name, 'full');

            return drupal_render($build);
        }
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function delete($node)
    {
        $response = $this
            ->client
            ->delete([
                'index' => $this->index,
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
            ->condition('index_key', $this->index)
            ->execute()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function matches($index, $node)
    {
        foreach ($this->moduleHandler->getImplementations(self::HOOK_NODE_INDEX) as $module) {
            if ($this->moduleHandler->invoke($module, self::HOOK_NODE_INDEX, [$index, $node])) {
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

        $params   = [];
        $nidList  = [];

        foreach ($nodeList as $key => $node) {

            if (!$force && !$this->matches($this->index, $node)) {
                unset($nodeList[$key]);
            }

            $params['body'][] = [
                'index' => [
                    '_index'   => $this->index,
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
            ->condition('index_key', $this->index)
            ->execute()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function upsert($node, $force = false, $refresh = false)
    {
        if (!$force && !$this->matches($this->index, $node)) {
            return false;
        }

        $response = $this
            ->client
            ->index([
                'index'   => $this->index,
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
            ->condition('index_key', $this->index)
            ->execute()
        ;

        return true;
    }
}