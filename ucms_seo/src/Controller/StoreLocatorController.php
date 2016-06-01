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
        $all_type_key = variable_get('ucms_seo_store_locator_type_all_key', 'all');
        $type = $type === $all_type_key ? null : $type;
        $sub_area = $sub_area === 'all' ? null : $sub_area;

        /** @var StoreLocatorInterface $storeLocator */
        $storeLocator = $this
            ->get('ucms_seo.store_locator_factory')
            ->create($node, $type, $sub_area, $locality);

        $build = [];

        if (node_is_page($node)) {
            $title = $storeLocator->getTitle();
            drupal_set_title($node->title.' '.$title);

            drupal_add_js([
                'storeLocator' => ['items' => $storeLocator->getMapItems()],
            ], 'setting');
            $build['map'] = [
                '#theme'    => 'ucms_seo_store_locator_map',
                '#items'    => $storeLocator->getMapItems(),
                '#nodes'    => $storeLocator->getNodes(),
                '#type'     => $storeLocator->getTypeLabel($type),
                '#sub_area' => $storeLocator->getSubAreaLabel(),
                '#locality' => $storeLocator->getLocalityLabel(),
            ];
        }

        $build['store_links'] = [
            '#theme' => 'links__ucms_seo__store_locator',
            '#links' => $storeLocator->getLinks(),
        ];

        return $build;
    }
}
