<?php

namespace MakinaCorpus\Ucms\Dashboard\Dashboard;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class DashboardEvent
 *
 * @package MakinaCorpus\Ucms\Dashboard\Dashboard
 */
class DashboardEvent extends Event
{

    /**
     * @var Dashboard
     */
    private $dashboard;

    /**
     * DashboardEvent constructor.
     *
     * @param \MakinaCorpus\Ucms\Dashboard\Dashboard\Dashboard $dashboard
     */
    public function __construct(Dashboard $dashboard)
    {
        $this->dashboard = $dashboard;
    }

    /**
     * @return \MakinaCorpus\Ucms\Dashboard\Dashboard\Dashboard
     */
    public function getDashboard()
    {
        return $this->dashboard;
    }

}
