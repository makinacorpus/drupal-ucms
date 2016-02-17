<?php

namespace MakinaCorpus\Ucms\Dashboard\Dashboard;

use MakinaCorpus\Ucms\Dashboard\Action\Action;

/**
 * Interface for portlets.
 */
interface DashboardPortletInterface
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
     * @TODO Could be rendered in an Ajax reqest someday
     * @return mixed
     */
    public function render();

}
