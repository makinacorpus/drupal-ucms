<?php

namespace MakinaCorpus\Ucms\Seo\StoreLocator;


use Drupal\node\NodeInterface;

abstract class AbstractStoreLocator implements StoreLocatorInterface {

    protected $node;
    protected $type;
    protected $subArea;
    protected $locality;

    public function __construct(NodeInterface $node, $type = null, $subArea = null, $locality = null)
    {
        $this->node = $node;
        $this->type = $type;
        $this->subArea = $subArea;
        $this->locality = $locality;
    }
}
