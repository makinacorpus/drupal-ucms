<?php

namespace MakinaCorpus\Ucms\Dashboard\Portlet;

use Drupal\Core\Session\AccountInterface;

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
        return $this->portlets;
    }

    /**
     * Get the list of portlets.
     *
     * @param AccountInterface $account
     *
     * @return PortletInterface[]
     */
    public function getPortletsForAccount(AccountInterface $account)
    {
        $portlets = [];
        foreach ($this->getPortlets() as $id => $portlet) {
            if ($portlet->userIsAllowed($account)) {
                $portlets[$id] = $portlet;
            }
        }

        return $portlets;
    }

    /**
     * Add a portlet to the list of portlets.
     *
     * @param PortletInterface $portlet
     * @param string $id
     */
    public function addPortlet(PortletInterface $portlet, $id)
    {
        $this->portlets[$id] = $portlet;
    }
}
