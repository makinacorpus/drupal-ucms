<?php

namespace MakinaCorpus\Ucms\Search;

use Drupal\node\NodeInterface;

class NodeIndexerChain implements NodeIndexerInterface
{
    /**
     * @var NodeIndexerInterface[]
     */
    private $chain = [];

    /**
     * Default constructor
     *
     * @param NodeIndexerInterface[] $indexers
     */
    public function __construct($indexers = [])
    {
        if ($indexers) {
            foreach ($indexers as $index => $indexer) {
                $this->addIndexer($index, $indexer);
            }
        }
    }

    /**
     * Removes a single indexer
     *
     * @param string $index
     */
    public function removeIndexer($index)
    {
        unset($this->chain[$index]);
    }

    /**
     * Adds a single indexer
     *
     * @param string $index
     * @param NodeIndexerInterface $indexer
     */
    public function addIndexer($index, NodeIndexerInterface $indexer)
    {
        $this->chain[$index] = $indexer;
    }

    /**
     * Get a single indexer
     *
     * @param string $index
     *
     * @return NodeIndexerInterface
     */
    public function getIndexer($index)
    {
        if (!isset($this->chain[$index])) {
            throw new \InvalidArgumentException(sprintf("Indexer for index '%s' is not registered", $index));
        }

        return $this->chain[$index];
    }

    /**
     * {inheritdoc}
     */
    public function enqueue(array $nodes)
    {
        foreach ($this->chain as $indexer) {
            $indexer->enqueue($nodes);
        }
    }

    /**
     * {inheritdoc}
     */
    public function dequeue()
    {
        foreach ($this->chain as $indexer) {
            $indexer->dequeue();
        }
    }

    /**
     * {inheritdoc}
     */
    public function bulkDequeue($limit = null)
    {
        $count = 0;

        foreach ($this->chain as $indexer) {
            $count += $indexer->bulkDequeue($limit);
        }

        return $count;
    }

    /**
     * {inheritdoc}
     */
    public function bulkMarkForReindex($nidList = null)
    {
        foreach ($this->chain as $indexer) {
            $indexer->bulkMarkForReindex($nidList);
        }
    }

    /**
     * {inheritdoc}
     */
    public function delete(NodeInterface $node)
    {
        foreach ($this->chain as $indexer) {
            $indexer->delete($node);
        }
    }

    /**
     * {inheritdoc}
     */
    public function matches(NodeInterface $node)
    {
        foreach ($this->chain as $indexer) {
            if ($indexer->matches($node)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {inheritdoc}
     */
    public function bulkUpsert($nodeList, $force = false, $refresh = false)
    {
        foreach ($this->chain as $indexer) {
            $indexer->bulkUpsert($nodeList, $force, $refresh);
        }
    }

    /**
     * {inheritdoc}
     */
    public function upsert(NodeInterface $node, $force = false, $refresh = false)
    {
        $ret = false;

        foreach ($this->chain as $indexer) {
            $ret |= $indexer->upsert($node, $force, $refresh);
        }

        return $ret;
    }
}
