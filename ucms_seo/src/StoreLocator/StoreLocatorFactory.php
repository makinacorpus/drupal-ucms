<?php

namespace MakinaCorpus\Ucms\Seo\StoreLocator;


use Drupal\node\NodeInterface;

class StoreLocatorFactory
{

    public function create(NodeInterface $node, $type, $subArea = null, $locality = null)
    {
        $class = variable_get('ucms_seo_store_locator_class', false);
        assert($class !== false, 'Drupal variable "ucms_seo_store_locator_class" must be defined.');
        return new $class($node, $type, $subArea, $locality);
    }
}
