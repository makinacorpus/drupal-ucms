<?php

namespace MakinaCorpus\Ucms\Dashboard;

use Drupal\node\NodeInterface;

class SmartObject
{
    const CONTEXT_CART = 'cart';
    const CONTEXT_LAYOUT = 'layout';
    const CONTEXT_UNODEREF = 'unoderef';

    private $node;
    private $context;

    public function __construct($node, $context)
    {
        $this->node = $node;
        $this->context = $context;
    }

    /**
     * @return NodeInterface
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }
}
