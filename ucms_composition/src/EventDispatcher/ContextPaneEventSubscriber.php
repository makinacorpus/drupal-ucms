<?php

namespace MakinaCorpus\Ucms\Composition\EventDispatcher;

use MakinaCorpus\Drupal\Dashboard\EventDispatcher\ContextPaneEvent;
use MakinaCorpus\Drupal\Layout\Event\CollectLayoutEvent;
use MakinaCorpus\Drupal\Layout\Form\LayoutContextEditForm;
use MakinaCorpus\Layout\Controller\Context;
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
    private $context;

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
                ['onUcmsdashboardContextinit', 0],
            ],
        ];
    }

    /**
     * Collects current page layout
     */
    public function onCollectLayout(CollectLayoutEvent $event)
    {
        // We manually display the form in the context pane, so we hide it
        // from the default content Drupal region
        $event->hideForm();

        if (!$this->siteManager->hasContext()) {
            return;
        }

        $layoutIds  = [];
        $layouts    = [];
        $site       = $this->siteManager->getContext();
        $theme      = $site->getTheme();
        $storage    = $event->getLayoutStorage();

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
                $layouts[] = $storage->create(['site_id' => $site->getId(), 'region' => $region]);
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
                    $layouts[] = $storage->create(['node_id' => $node->nid, 'site_id' => $site->getId(), 'region' => $region]);
                }
            } else {
                $layoutIds = array_merge($layoutIds, $nodeLayoutIds);
            }
        }

        /*
         * Load everything at once if possible
         */

        if ($layoutIds) {
            $layouts = array_merge($layouts, $storage->loadMultiple($layoutIds));
        }

        // @todo access:
        //   - for webmaster, layout in global regions
        //   - for others, only if node is editable
        //   - and we need to load home page layouts too
        foreach ($layouts as $layout) {
            $event->addLayout($layout, true);
        }
    }

    /**
     * @param ContextPaneEvent $event
     */
    public function onUcmsdashboardContextinit(ContextPaneEvent $event)
    {
        $contextPane = $event->getContextPane();

        // Set the 'cart' tab as default tab as soon as the user is in edit mode
        // or editing a content (unoderef and media fields) or when he is in
        // content admin (to drag and drop content to his cart).
        if ($contextPane->hasTab('cart')) {

            $defaultTabRoutes = ['admin/dashboard/content', 'admin/dashboard/media', 'node/%/edit'];
            $router_item = menu_get_item();

            if (
                $this->contextManager->isInEditMode() ||
                in_array($router_item['path'], $defaultTabRoutes) ||
                in_array($router_item['tab_parent'], $defaultTabRoutes)
            ) {
                $contextPane->setDefaultTab('cart');
            }
        }

        $site = $this->siteManager->getContext();
        $account = \Drupal::currentUser();
        // @todo this should check for any layout at all being here
        if (!path_is_admin(current_path()) && $site && $this->siteManager->getAccess()->userIsWebmaster($account, $site)) {
            $form = \Drupal::formBuilder()->getForm(LayoutContextEditForm::class);
            $contextPane->addActions($form);
        }

        if ($this->context->hasToken()) {
            $contextPane->setDefaultTab('cart');
        }
    }
}
