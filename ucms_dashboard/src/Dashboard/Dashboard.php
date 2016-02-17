<?php

namespace MakinaCorpus\Ucms\Dashboard\Dashboard;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class Dashboard
 *
 * @package MakinaCorpus\Ucms\Dashboard\Dashboard
 */
class Dashboard
{

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var DashboardPortletInterface[]
     */
    private $portlets = [];

    /**
     * Dashboard constructor.
     *
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Get the list of portlets.
     *
     * @return DashboardPortletInterface[]
     */
    public function getPortlets()
    {
        ksort($this->portlets);

        return $this->portlets;
    }

    /**
     * Add a portlet to the list of portlets.
     *
     * @param DashboardPortletInterface $portlet
     * @param float $position
     */
    public function addPortlet(DashboardPortletInterface $portlet, $position = 0.0)
    {
        while (isset($this->portlets[(string)$position])) {
            $position += 0.01;
        }
        $this->portlets[(string)$position] = $portlet;
    }

    /**
     * Initialize the DashboardEvent.
     *
     * @return $this
     */
    public function init()
    {
        $event = new DashboardEvent($this);

        $this->dispatcher->dispatch('ucms_dashboard.dashboard_init', $event);

        return $this;
    }

}
