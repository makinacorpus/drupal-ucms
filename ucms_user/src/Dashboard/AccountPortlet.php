<?php

namespace MakinaCorpus\Ucms\User\Dashboard;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Dashboard\DashboardPortlet;

/**
 * Class AccountPortlet
 * @package MakinaCorpus\Ucms\User\Dashboard
 */
class AccountPortlet extends DashboardPortlet
{
    use StringTranslationTrait;

    /**
     * Return the title of this portlet.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->t("My account");
    }

    /**
     * Return the path for the main page of this portlet.
     *
     * @return null|string
     */
    public function getPath()
    {
        return null;
    }

    /**
     * @return Action[]
     */
    public function getActions()
    {
        return [];
    }

    /**
     * Render the content of this portlet.
     *
     * @TODO Could be rendered in an Ajax reqest someday
     * @return mixed
     */
    public function render()
    {
        // TODO: Implement render() method.
    }
}
