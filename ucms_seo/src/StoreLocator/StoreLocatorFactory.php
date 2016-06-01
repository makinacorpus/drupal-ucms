<?php

namespace MakinaCorpus\Ucms\Seo\StoreLocator;

use Drupal\node\NodeInterface;

use MakinaCorpus\Ucms\Seo\SeoService;

class StoreLocatorFactory
{
    /**
     * @var SeoService
     */
    private $service;

    public function __construct(SeoService $service)
    {
        $this->service = $service;
    }

    /**
     * @param NodeInterface $node
     * @param string $type
     * @param string $subArea
     * @param string $locality
     *
     * @return StoreLocatorInterface
     */
    public function create(NodeInterface $node = null, $type = null, $subArea = null, $locality = null)
    {
        $class = variable_get('ucms_seo_store_locator_class', false);
        assert($class !== false, 'Drupal variable "ucms_seo_store_locator_class" must be defined.');

        return new $class($this->service, $node, $type, $subArea, $locality);
    }
}
