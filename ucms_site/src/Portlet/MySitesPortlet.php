<?php

namespace MakinaCorpus\Ucms\Site\Portlet;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Portlet\Portlet;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\SiteManager;

/**
 * Class SitesPortlet
 *
 * @package MakinaCorpus\Ucms\Site\Dashboard
 */
class MySitesPortlet extends Portlet
{
    use StringTranslationTrait;

    /**
     * @var \stdClass
     */
    private $account;

    /**
     * @var SiteManager
     */
    private $siteManager;

    /**
     * SitePortlet constructor.
     * @param SiteManager $siteManager
     */
    public function __construct(SiteManager $siteManager)
    {
        $this->siteManager = $siteManager;
    }

    /**
     * Return the title of this portlet.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->t("My sites");
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
    public function getContent()
    {
        // TODO
        // $sites = $this->siteManager->loadWebmasterSites($this->account->uid);
        return '@todo';
    }

    /**
     * Return true if portlet if visible for user.
     *
     * @param $account
     * @return mixed
     */
    public function userIsAllowed(\stdClass $account)
    {
        $this->account = $account;
        return user_access(Access::PERM_SITE_REQUEST, $this->account);
    }
}
