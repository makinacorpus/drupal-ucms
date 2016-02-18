<?php

namespace MakinaCorpus\Ucms\Dashboard\Portlet;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class Dashboard
 *
 * @package MakinaCorpus\Ucms\Dashboard\Dashboard
 */
class PortletRegistry
{

    /**
     * @var PortletInterface[]
     */
    private $portlets = [];

    /**
     * Get the list of portlets.
     *
     * @return PortletInterface[]
     */
    public function getPortlets()
    {
        ksort($this->portlets);

        return $this->portlets;
    }

    /**
     * Get the list of portlets.
     *
     * @param \stdClass $account
     * @return PortletInterface[]
     */
    public function getPortletsForAccount(\stdClass $account)
    {
        $portlets = [];
        foreach ($this->getPortlets() as $portlet) {
            if ($portlet->userIsAllowed($account)) {
                $portlets[] = $portlet;
            }
        }

        return $portlets;
    }

    /**
     * Add a portlet to the list of portlets.
     *
     * @param PortletInterface $portlet
     * @param float $position
     */
    public function addPortlet(PortletInterface $portlet, $position = 0.0)
    {
        while (isset($this->portlets[(string)$position])) {
            $position += 0.01;
        }
        $this->portlets[(string)$position] = $portlet;
    }
}
