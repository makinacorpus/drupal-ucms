<?php

namespace MakinaCorpus\Ucms\Dashboard\Portlet;

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
    // @TODO Could be rendered in an Ajax reqest someday
    public function getContent();

    /**
     * Return true if portlet if visible for user.
     *
     * @param \stdClass $account
     * @return bool
     */
    public function userIsAllowed(\stdClass $account);

}
