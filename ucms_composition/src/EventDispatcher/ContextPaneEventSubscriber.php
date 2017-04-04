<?php

namespace MakinaCorpus\Ucms\Composition\EventDispatcher;

use MakinaCorpus\Drupal\Dashboard\EventDispatcher\ContextPaneEvent;
use MakinaCorpus\Drupal\Layout\Event\CollectLayoutEvent;
use MakinaCorpus\Drupal\Layout\Form\LayoutContextEditForm;
use MakinaCorpus\Layout\Controller\Context;
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
        $event->hideForm();

        if (arg(0) !== 'node' && arg(2)) {
            return [];
        }
        if (!$node = menu_get_object()) {
            return [];
        }
        if (!$this->siteManager->hasContext()) {
            return;
        }

        $site = $this->siteManager->getContext();

        $layoutIdList = $this
            ->database
            ->query(
                "select id from {layout} where node_id = ? and site_id = ?",
                [$node->nid, $site->getId()]
            )
            ->fetchCol()
        ;

        if ($layoutIdList) {
            $layouts = $event->getLayoutStorage()->loadMultiple($layoutIdList);
        } else {
            // Automatically creates new layout for node if none exist
            $layouts = [$event->getLayoutStorage()->create(['node_id' => $node->nid, 'site_id' => $site->getId()])];
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
