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
