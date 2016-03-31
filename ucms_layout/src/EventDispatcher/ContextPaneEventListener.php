<?php

namespace MakinaCorpus\Ucms\Layout\EventDispatcher;

use MakinaCorpus\Ucms\Dashboard\EventDispatcher\ContextPaneEvent;
use MakinaCorpus\Ucms\Site\SiteManager;

/**
 * Class ContextPaneEventListener
 * @package MakinaCorpus\Ucms\Contrib\EventDispatcher
 */
class ContextPaneEventListener
{
    /**
     * @var SiteManager
     */
    private $siteManager;

    /**
     * Default constructor
     *
     * @param SiteManager $siteManager
     */
    public function __construct(SiteManager $siteManager)
    {
        $this->siteManager = $siteManager;
    }

    /**
     * @param ContextPaneEvent $event
     */
    public function onUcmsdashboardContextinit(ContextPaneEvent $event)
    {
        $contextPane = $event->getContextPane();
        $manager = $this->siteManager;

        $site = $site = $manager->getContext();
        $account = \Drupal::currentUser();
        // @todo this should check for any layout at all being here
        if (!path_is_admin(current_path()) && $site && $manager->getAccess()->userIsWebmaster($account, $site)) {
            $form = \Drupal::formBuilder()->getForm('MakinaCorpus\Ucms\Layout\Form\LayoutContextEditForm');
            $contextPane->addActions($form);
        }
    }
}
