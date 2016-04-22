<?php

namespace MakinaCorpus\Ucms\Search\Attachment;

use Drupal\Core\Entity\EntityManager;
use Drupal\node\NodeInterface;

use Elasticsearch\Client;

abstract class AbstractNodeAttachmentIndexer implements NodeAttachmentIndexerInterface
{

    /**
     * The DRUPAL index name
     * @var string
     */
    protected $index;

    /**
     * The ES index/alias name
     * @var string
     */
    protected $indexRealname;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var \DatabaseConnection
     */
    protected $db;

    /**
     * @var EntityStorageInterface
     */
    protected $nodeStorage;

    /**
     * @var integer
     */
    protected $bulkLimit = null;

    /**
     * The total of files size to index per request againsts ES. The unit is mo.
     * @var float
     */
    protected $bulkMaxSizeBytes = null;

    protected $totalSizeBytes = 0;

    public function __construct(
      $index,
      Client $client,
      \DatabaseConnection $db,
      EntityManager $entityManager,
      $indexRealname = null
    ) {
        $this->index = $index;
        $this->indexRealname = ($indexRealname ? $indexRealname : $index);
        $this->client = $client;
        $this->db = $db;
        $this->nodeStorage = $entityManager->getStorage('node');

        $this->bulkLimit = variable_get('ucms_search_attachment_bulk_limit', 50);
        $this->bulkMaxSizeBytes = variable_get('ucms_search_attachment_bulk_max_size_mo', 50) * 1000 * 1000;
    }

    public function bulkAttachmentDequeue()
    {
        $nids = $this
          ->db
          ->select('ucms_search_status', 's')
          ->fields('s', ['nid'])
          ->condition('s.index_key', $this->index)
          ->condition('s.needs_attachments_reindex', 1)
          ->orderBy('nid', 'ASC')
          // This limit MUST be very low and heavily rely on max file size
          // upload allowed
          ->range(0, $this->bulkLimit)
          ->execute()
          ->fetchCol();

        if ($nids) {
            $this->bulkAttachmentUpsert(
              $this->nodeStorage->loadMultiple($nids)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function bulkMarkAttachmentForReindex(array $nids = null)
    {
        // First of all, check whether we have eligible nodes for indexing (i.e
        //: no media fields define?)
        $subquery = $this->getSubselectQueryMarkReindex($nids);
        if (!$subquery) {
            return;
        }

        // TODO - FIXME ugly fix due to bad design. Sometime, we may been called
        // in case an upsert op is done. In this case, the entry doesn't exists
        // yet in database. And sometime, it may already exists so MERGE query
        // is sometime needed here.
        if (count($nids) === 1) {

            // Check whether this node is eligible for indexing.
            $nid = $subquery->execute()->fetchField();
            if ($nid) {

                $this
                  ->db
                  ->merge('ucms_search_status')
                  ->key(
                    [
                      'nid' => $nid,
                      'index_key' => $this->index,
                    ]
                  )
                  ->fields([
                    'needs_attachments_reindex' => 1,
                  ])
                  ->execute();
            }
        } else {
            // Entries MUST exist (UPDATE query).
            $this
                ->db
                ->update('ucms_search_status')
                ->fields([
                    'needs_attachments_reindex' => 1,
                ])
                ->condition('index_key', $this->index)
                ->condition('nid', $subquery, 'IN')
                ->execute();
        }
    }

    /**
     * Update ES documents with attachments. Multiple attachments per document
     * is allowed but be careful with memory usage!
     *
     * @param \Drupal\node\NodeInterface[] $nodes
     */
    protected function bulkAttachmentUpsert(array $nodes)
    {
        $nids = [];
        $params = [];

        $this->totalSizeBytes = 0;  // Don't forgot to reset it!!
        foreach ($nodes as $node) {

            // First of all, try to get encoded files.
            try {
                $attachments = $this->getEncodedAttachments($node);
            } catch (MaxSizeAttachmentExceedException $e) {
                // Log any nodes which are no more indexed.
                watchdog(
                  'ucms_search_attachment',
                  '!nid attachments not indexed because max file size is exceed',
                  ['!nid' => $node->nid],
                  WATCHDOG_NOTICE
                );
                continue;
            }

            // Even if there is no attachment, mark it as treated.
            $nids[] = $node->id();

            if ($attachments) {
                $params['body'][] = [
                  'index' => [
                    '_index' => $this->indexRealname,
                    '_id' => $node->id(),
                    '_type' => 'node',
                  ],
                ];
                $params['body'][] = [
                  'attachment' => $attachments,
                ];
            }
        }

        if ($params) {
            $this->client->bulk($params);
        }

        // Yes, it may never happen but we can have an infinite loop if we got
        // only ONE node which exceed the total limit and throw the exception...
        if ($nids) {
            // Don't delete but update it only (in case other operations are
            // still needed).
            $this
              ->db
              ->update('ucms_search_status')
              ->fields(['needs_attachments_reindex' => 0])
              ->condition('nid', $nids)
              ->condition('index_key', $this->index)
              ->execute();
        }
    }

    /**
     * Returns a query which must returns a list of nids to reindex for
     * attachments.
     *
     * @param array $nids
     *   An optionnal list of nids to filter on.
     *
     * @return
     *   A QuerySelect or null.
     */
    abstract protected function getSubselectQueryMarkReindex(array $nids = null);

    /**
     * Returns an array of base64 encoded content file.
     *
     * @param NodeInterface $node
     * 
     * @throws MaxSizeAttachmentExceedException
     *   If max size is exceed, this exception must be raised.
     */
    abstract protected function getEncodedAttachments(NodeInterface $node);
}
