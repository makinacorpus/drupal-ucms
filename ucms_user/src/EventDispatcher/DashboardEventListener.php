<?php

namespace MakinaCorpus\Ucms\User\EventDispatcher;


use MakinaCorpus\Ucms\Dashboard\Dashboard\DashboardEvent;
use MakinaCorpus\Ucms\User\Dashboard\AccountPortlet;

/**
 * Class DashboardEventListener
 *
 * @package MakinaCorpus\Ucms\User\EventDispatcher
 */
class DashboardEventListener
{
    /**
     * Event: On ContextPane init.
     *
     * @param DashboardEvent $event
     */
    public function onUcmsdashboardDashboardinit(DashboardEvent $event)
    {
        $event->getDashboard()
              ->addPortlet(new AccountPortlet());
    }
}
