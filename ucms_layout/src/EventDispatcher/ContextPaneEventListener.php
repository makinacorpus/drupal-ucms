<?php

namespace MakinaCorpus\Ucms\Layout\EventDispatcher;

use MakinaCorpus\Ucms\Dashboard\EventDispatcher\ContextPaneEvent;
use MakinaCorpus\Ucms\Layout\ContextManager;
use MakinaCorpus\Ucms\Layout\Form\LayoutContextEditForm;
use MakinaCorpus\Ucms\Site\SiteManager;

/**
 * Adds layout edit actions and form to UI.
 */
final class ContextPaneEventListener
{
    private $siteManager;
    private $contextManager;

    /**
     * Default constructor
     *
     * @param SiteManager $siteManager
     */
    public function __construct(SiteManager $siteManager, ContextManager $contextManager)
    {
        $this->siteManager = $siteManager;
        $this->contextManager = $contextManager;
    }

    /**
     * @param ContextPaneEvent $event
     */
    public function onUcmsdashboardContextinit(ContextPaneEvent $event)
    {
        $contextPane = $event->getContextPane();
        $manager = $this->siteManager;

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

        $site = $manager->getContext();
        $account = \Drupal::currentUser();
        // @todo this should check for any layout at all being here
        if (!path_is_admin(current_path()) && $site && $manager->getAccess()->userIsWebmaster($account, $site)) {
            $form = \Drupal::formBuilder()->getForm(LayoutContextEditForm::class);
            $contextPane->addActions($form);
        }

        if ($this->contextManager->isInEditMode()) {
            $contextPane->setDefaultTab('cart');
        }
    }
}
