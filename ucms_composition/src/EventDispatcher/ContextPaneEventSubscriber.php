<?php

namespace MakinaCorpus\Ucms\Composition\EventDispatcher;

use MakinaCorpus\Drupal\Calista\EventDispatcher\ContextPaneEvent;
use MakinaCorpus\Layout\Context\Context;
use MakinaCorpus\Layout\EventDispatcher\CollectLayoutEvent;
use MakinaCorpus\Ucms\Composition\RegionConfig;
use MakinaCorpus\Ucms\Site\SiteManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds layout edit actions and form to UI.
 */
final class ContextPaneEventSubscriber implements EventSubscriberInterface
{
    private $siteManager;
    private $database;

    /**
     * Default constructor
     *
     * @param SiteManager $siteManager
     * @param \DatabaseConnection $database
     * @param Context $context
     */
    public function __construct(SiteManager $siteManager, \DatabaseConnection $database, Context $context)
    {
        $this->siteManager = $siteManager;
        $this->database = $database;
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            CollectLayoutEvent::EVENT_NAME => [
                ['onCollectLayout', 0],
            ],
            ContextPaneEvent::EVENT_INIT => [
                ['onContextInit', 0],
            ],
        ];
    }

    /**
     * Collects current page layout
     */
    public function onCollectLayout(CollectLayoutEvent $event)
    {
        if (!$this->siteManager->hasContext()) {
            return;
        }

        $layoutIds  = [];
        $context    = $event->getContext();
        $site       = $this->siteManager->getContext();
        $theme      = $site->getTheme();
        $storage    = $context->getLayoutStorage();

        /*
         * Collect site layout
         */

        $siteLayoutIds = $this
            ->database
            ->query(
                "select id from {layout} where site_id = ? and node_id is null",
                [$site->getId()]
            )
            ->fetchCol()
        ;

        if (!$siteLayoutIds) {
            // Automatically create regions for site
            foreach (RegionConfig::getSiteRegionList($theme) as $region) {
                $layoutIds[] = $storage->create(['site_id' => $site->getId(), 'region' => $region])->getId();
            }
        } else {
            $layoutIds = array_merge($layoutIds, $siteLayoutIds);
        }

        /*
         * Collect current node layouts, if any
         */

        if (arg(0) === 'node' && !arg(2) && ($node = menu_get_object())) {

            $nodeLayoutIds = $this
                ->database
                ->query(
                    "select id from {layout} where node_id = ? and site_id = ?",
                    [$node->nid, $site->getId()]
                )
                ->fetchCol()
            ;

            if (!$nodeLayoutIds) {
                // Automatically creates new layout for node if none exist
                foreach (RegionConfig::getPageRegionList($theme) as $region) {
                    $layoutIds[] = $storage->create(['node_id' => $node->nid, 'site_id' => $site->getId(), 'region' => $region])->getId();
                }
            } else {
                $layoutIds = array_merge($layoutIds, $nodeLayoutIds);
            }
        }

        if ($layoutIds) {
            $event->addLayoutList($layoutIds);
        }
    }

    /**
     * @param ContextPaneEvent $event
     */
    public function onContextInit(ContextPaneEvent $event)
    {
        $contextPane = $event->getContextPane();

        // Set the 'cart' tab as default tab as soon as the user is in edit mode
        // or editing a content (unoderef and media fields) or when he is in
        // content admin (to drag and drop content to his cart).
        if ($contextPane->hasTab('cart')) {

            $defaultTabRoutes = ['admin/dashboard/content', 'admin/dashboard/media', 'node/%/edit'];
            $router_item = menu_get_item();

            if (
                $this->context->hasToken() ||
                in_array($router_item['path'], $defaultTabRoutes) ||
                in_array($router_item['tab_parent'], $defaultTabRoutes)
            ) {
                $contextPane->setDefaultTab('cart');
            }
        }

        if ($this->context->hasToken()) {
            $contextPane->setDefaultTab('cart');
        }
    }
}
