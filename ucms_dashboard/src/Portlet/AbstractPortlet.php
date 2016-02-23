<?php

namespace MakinaCorpus\Ucms\Dashboard\Portlet;

use Drupal\Core\Session\AccountInterface;

abstract class AbstractPortlet implements PortletInterface
{
    /**
     * @var AccountInterface
     */
    private $account;

    /**
     * {@inheritdoc}
     */
    public function setAccount(AccountInterface $account)
    {
        $this->account = $account;
    }

    /**
     * Get current account
     *
     * @return AccountInterface
     */
    protected function getAccount()
    {
        return $this->account;
    }

    /**
     * {@inheritdoc}
     */
    public function renderActions()
    {
        return [
            '#theme'    => 'ucms_dashboard_actions',
            '#actions'  => $this->getActions(),
        ];
    }
}
