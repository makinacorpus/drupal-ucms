<?php

namespace MakinaCorpus\Ucms\Dashboard\Portlet;

use Drupal\Core\Session\AccountInterface;

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
        $ret = [];

        foreach ($this->getPortlets() as $id => $portlet) {
            if ($portlet->userIsAllowed($account)) {
                $portlet->setAccount($account);
                $ret[$id] = $portlet;
            }
        }

        return $ret;
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
