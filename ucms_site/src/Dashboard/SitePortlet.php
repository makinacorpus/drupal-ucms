<?php

namespace MakinaCorpus\Ucms\Site\Dashboard;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Dashboard\DashboardPortlet;

/**
 * Class SitePortlet
 *
 * @package MakinaCorpus\Ucms\Site\Dashboard
 */
class SitePortlet extends DashboardPortlet
{
    use StringTranslationTrait;

    /**
     * Return the title of this portlet.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->t("Sites");
    }

    /**
     * Return the path for the main page of this portlet.
     *
     * @return null|string
     */
    public function getPath()
    {
        return 'admin/dashboard/site';
    }

    /**
     * @return Action[]
     */
    public function getActions()
    {
        return [
            new Action($this->t("Request site"), 'admin/dashboard/site/request', null, 'globe', 0, true, true),
        ];
    }

    /**
     * Render the content of this portlet.
     *
     * @TODO Could be rendered in an Ajax reqest someday
     * @return mixed
     */
    public function render()
    {
        return [];
    }
}
