<?php

namespace MakinaCorpus\Ucms\Cart\Cart;

use Drupal\node\NodeInterface;

/**
 * (Almost) immutable object for reading and presentation
 */
final class CartItem
{
    private $nid;
    private $uid;
    private $added;
    private $node;

    /**
     * Default constructor
     *
     * It will be skipped in most cases, because it will be loaded via PDO.
     *
     * @param int $nodeId
     * @param int $userId
     * @param int|string|\DateTimeInterface $added
     * @param NodeInterface $node
     */
    public function __construct($nodeId = null, $userId = null, $added = null, NodeInterface $node = null)
    {
        if (null !== $nodeId) {
            $this->nid = (int)$nodeId;
        }
        if (null !== $userId) {
            $this->uid = (int)$userId;
        }
        if (null !== $added) {
            $this->added = $added;
        }
        if (null !== $node) {
            $this->setNode($node);
        }
    }

    public function setNode(NodeInterface $node)
    {
        if ($node->id() != $this->nid) {
            throw new \InvalidArgumentException(sprintf("node identifier mismatch, %d expected, %s given", $this->nodeId, $node->id()));
        }

        $this->node = $node;
    }

    public function getNodeId()
    {
        return (int)$this->nid;
    }

    public function getUserId()
    {
        return (int)$this->uid;
    }

    public function getAdded()
    {
        if ($this->added && !$this->added instanceof \DateTimeInterface) {
            if (is_string($this->added)) {
                $this->added = \DateTime::createFromFormat('Y-m-d H:i:s', $this->added);
            } else if (is_numeric($this->added)) {
                $this->added = new \DateTime('@' . $this->added);
            }
        }

        return $this->added;
    }

    public function getNode()
    {
        if (!$this->node) {
            throw new \LogicException("imcomplete object");
        }

        return $this->node;
    }
}
