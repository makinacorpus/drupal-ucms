<?php

namespace MakinaCorpus\Ucms\Seo\StoreLocator;

use Drupal\node\NodeInterface;

use MakinaCorpus\Ucms\Seo\SeoService;

abstract class AbstractStoreLocator implements StoreLocatorInterface
{
    protected $service;
    protected $node;
    protected $type;
    protected $subArea;
    protected $locality;

    public function __construct(SeoService $service, NodeInterface $node = null, $type = null, $subArea = null, $locality = null)
    {
        $this->service = $service;
        $this->node = $node;
        $this->type = $type;
        $this->subArea = $subArea;
        $this->locality = $locality;
    }
}
