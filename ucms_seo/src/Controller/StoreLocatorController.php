<?php

namespace MakinaCorpus\Ucms\Seo\Controller;


use Drupal\node\NodeInterface;
use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Seo\StoreLocator;

class StoreLocatorController extends Controller
{

    public function renderPageAction(NodeInterface $node, $type, $sub_area = null, $locality = null)
    {
        $type = $type === 'all' ? null : $type;
        $sub_area = $sub_area === 'all' ? null : $sub_area;

        $storeLocator = $this
            ->get('ucms_seo.store_locator_factory')
            ->create($node, $type, $sub_area, $locality);

        $title = $storeLocator->getTitle();
        drupal_set_title($title);

        $items = $storeLocator->getMapItems();
        $links = $storeLocator->getLinks();

        drupal_add_js([
          'storeLocator' => ['items' => $items],
        ], 'setting');

        return [
            'map' => [
                '#theme' => 'ucms_seo_store_locator_map',
                '#items' => $items,
            ],
            'links' => [
                '#theme' => 'links__ucms_seo__store_locator',
                '#links' => $links,
            ],
        ];
    }
}
