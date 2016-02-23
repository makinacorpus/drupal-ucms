<?php

namespace MakinaCorpus\Ucms\Dashboard\Portlet;

use Drupal\Core\Session\AccountInterface;

use MakinaCorpus\Ucms\Dashboard\Action\Action;

/**
 * Interface for portlets.
 */
interface PortletInterface
{
    /**
     * Return the title of this portlet.
     *
     * @return string
     */
    public function getTitle();

    /**
     * Return the path for the main page of this portlet.
     *
     * @return null|string
     */
    public function getPath();

    /**
     * @return Action[]
     */
    public function getActions();

    /**
     * Render the content of this portlet.
     *
     * @return []
     */
    public function getContent();

    /**
     * Return true if portlet if visible for user.
     *
     * @param AccountInterface $account
     *
     * @return boolean
     */
    public function userIsAllowed(AccountInterface $account);

    /**
     * Set account to work with
     *
     * @param AccountInterface $account
     */
    public function setAccount(AccountInterface $account);
}
