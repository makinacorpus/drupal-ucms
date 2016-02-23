<?php

namespace MakinaCorpus\Ucms\Search;

use Drupal\node\NodeInterface;

interface NodeIndexerInterface
{
    /**
     * Enqueue node for later processing
     *
     * FIXME The queue should be persistent in order to avoid loosing data
     * in case of PHP crash or any other bugguy reason.
     *
     * @param NodeInterface[] $nodes
     */
    public function enqueue(array $nodes);

    /**
     * Dequeue temporary static queue at the end of the request
     */
    public function dequeue();

    /**
     * Dequeue and index items
     *
     * @param int $limit
     */
    public function bulkDequeue($limit = null);

    /**
     * Mark all content for reindexing in an index.
     *
     * @param int|int[] $nidList
     *   List of node identifiers to reindex or delete, if none given will work
     *   on all nodes of the site
     */
    public function bulkMarkForReindex($nidList = null);

    /**
     * Remove a single node from index
     *
     * @param NodeInterface $node
     */
    public function delete(NodeInterface $node);

    /**
     * Tell if the given node matches the given index.
     *
     * @param NodeInterface $node
     *
     * @return boolean
     */
    public function matches(NodeInterface $node);

    /**
     * Index or upsert given nodes using a bulk request
     *
     * Note that Elastic Search will be as fast if you choose to do an explicit
     * upsert or attempt to index a document with an already existing identifier,
     * reason why we actually choose to only send index operations, this way we
     * don't have to check ourselves if the document exists in the index or not.
     * Elastic Search index operation is actually an upsert operation.
     *
     * @param NodeInterface[] $nodeList
     *   Node object
     * @param boolean $force
     *   Internal boolean, skip match test, this should never be used
     *   outside of this module
     * @param boolean $refresh
     *   Explicit refresh set to true for ElasticSearch, forcing the full shard
     *   to be in sync for the next search
     */
    public function bulkUpsert($nodeList, $force = false, $refresh = false);

    /**
     * Index or upsert a single node
     *
     * Note that Elastic Search will be as fast if you choose to do an explicit
     * upsert or attempt to index a document with an already existing identifier,
     * reason why we actually choose to only send index operations, this way we
     * don't have to check ourselves if the document exists in the index or not.
     * Elastic Search index operation is actually an upsert operation.
     *
     * @param NodeInterface $node
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
    public function upsert(NodeInterface $node, $force = false, $refresh = false);
}
