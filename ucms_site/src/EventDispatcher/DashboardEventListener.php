<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;


use MakinaCorpus\Ucms\Dashboard\Dashboard\DashboardEvent;
use MakinaCorpus\Ucms\Site\Dashboard\SitePortlet;

/**
 * Class DashboardEventListener
 *
 * @package MakinaCorpus\Ucms\Site\EventDispatcher
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
              ->addPortlet(new SitePortlet());
    }
}
