<?php

namespace MakinaCorpus\Ucms\Seo\Controller;


use Drupal\node\NodeInterface;
use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Layout\ContextManager;
use MakinaCorpus\Ucms\Seo\StoreLocator;
use MakinaCorpus\Ucms\Seo\StoreLocator\StoreLocatorInterface;
use MakinaCorpus\Ucms\Site\SiteManager;

class StoreLocatorController extends Controller
{

    /**
     * This renders the route node/%/sotre-locator
     */
    public function renderPageAction(NodeInterface $node, $type, $sub_area = null, $locality = null)
    {
        /** @var SiteManager $siteManager */
        $siteManager = $this->get('ucms_site.manager');
        if ($siteManager->hasContext()) {
            /** @var ContextManager $layoutContext */
            $layoutContext = $this->get('ucms_layout.context_manager');
            $layoutContext->getPageContext()
                          ->setCurrentLayoutNodeId($node->id(), $siteManager->getContext()->getId())
            ;
        }

        return node_view($node);
    }

    /**
     * This renders the field when viewing a store_locator bundle
     */
    public function renderFieldAction(NodeInterface $node, $type, $sub_area = null, $locality = null)
    {
        $type = $type === 'all' ? null : $type;
        $sub_area = $sub_area === 'all' ? null : $sub_area;

        /** @var StoreLocatorInterface $storeLocator */
        $storeLocator = $this
            ->get('ucms_seo.store_locator_factory')
            ->create($node, $type, $sub_area, $locality);

        $title = $storeLocator->getTitle();
        drupal_set_title($node->title . ' ' . $title);

        $items = $storeLocator->getMapItems();
        $links = $storeLocator->getLinks();

        drupal_add_js([
          'storeLocator' => ['items' => $items],
        ], 'setting');

        return [
            'map' => [
                '#theme' => 'ucms_seo_store_locator_map',
                '#items' => $items,
                '#node'  => $node,
                '#type'  => $storeLocator->getTypeLabel($type),
                '#sub_area'  => $storeLocator->getSubAreaLabel(),
                '#locality'  => $storeLocator->getLocalityLabel(),
            ],
            'store_links' => [
                '#theme' => 'links__ucms_seo__store_locator',
                '#links' => $links,
            ],
        ];
    }
}
