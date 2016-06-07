<?php

namespace MakinaCorpus\Ucms\Search;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\node\NodeInterface;

use Elasticsearch\Client;

use MakinaCorpus\Ucms\Search\Attachment\NodeAttachmentIndexerInterface;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use MakinaCorpus\Ucms\Search\EventDispatcher\NodeIndexEvent;

/**
 * @todo unit test me
 */
class NodeIndexer implements NodeIndexerInterface
{
    const EVENT_INDEX = 'ucms_search:index';

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
     * @var NodeAttachmentIndexerInterface
     */
    private $attachmentIndexer = null;

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
        EventDispatcherInterface $eventDispatcher,
        $indexRealname = null,
        $preventBulkUsage = false)
    {
        $this->index = $index;
        $this->client = $client;
        $this->db = $db;
        $this->nodeStorage = $entityManager->getStorage('node');
        $this->moduleHandler = $moduleHandler;
        $this->eventDispatcher = $eventDispatcher;
        $this->indexRealname = ($indexRealname ? $indexRealname : $index);
        $this->preventBulkUsage = $preventBulkUsage;
        // TODO - FIXME inject me or rewrite me completly
        $this->attachmentIndexer = \Drupal::service('ucms_search.attachment_indexer');
    }

    /**
     * {@inheritdoc}
     */
    public function enqueue(array $nodes)
    {
        foreach ($nodes as $node) {
            if (is_numeric($node)) {
                $this->nodeQueue[(int)$node] = (int)$node;
            } else if ($node instanceof NodeInterface) {
                $this->nodeQueue[$node->nid] = $node->nid;
            } else {
                throw new \InvalidArgumentException("enqueued items must be \Drupal\node\NodeInterface instances or integers");
            }
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

        if (null !== $nidList) {
            $query->condition('n.nid', $nidList);
        }

        $this
            ->db
            ->insert('ucms_search_status')
            ->from($query)
            ->execute()
        ;

        // FIXME - fix spaghetti to do it in proper place.
        if ($this->attachmentIndexer) {
            $this->attachmentIndexer->bulkMarkAttachmentForReindex($nidList);
        }
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

        $values = [
            'title'       => $node->getTitle(),
            'id'          => $node->id(),
            'owner'       => $node->getOwnerId(),
            'created'     => $created ? $created->format(\DateTime::ISO8601) : null,
            'updated'     => $changed ? $changed->format(\DateTime::ISO8601) : null,
            'type'        => $node->getType(),
            'body'        => [],
            'status'      => (int)$node->isPublished(),
            'tags'        => [],
            'is_starred'  => (bool)$node->is_starred,
            'is_flagged'  => (bool)$node->is_flagged,
            'is_global'   => (bool)$node->is_global,
            'is_group'    => (bool)$node->is_group,
            'is_locked'   => !(bool)$node->is_clonable,
            'node_access' => $this->nodeExtractGrants($node),
            'site_id'     => $node->ucms_sites,
        ];

        $event = new NodeIndexEvent($node, $values);
        $this->eventDispatcher->dispatch(self::EVENT_INDEX, $event);

        return $event->getValues();
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

        // FIXME - fix spaghetti to do it in proper place.
        if ($this->attachmentIndexer) {
            $this->attachmentIndexer->bulkMarkAttachmentForReindex([$node->nid]);
        }

        return true;
    }
}
