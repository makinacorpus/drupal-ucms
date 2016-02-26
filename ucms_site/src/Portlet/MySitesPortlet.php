<?php

namespace MakinaCorpus\Ucms\Site\Portlet;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Dashboard\Portlet\AbstractAdminPortlet;
use MakinaCorpus\Ucms\Site\Access;

class MySitesPortlet extends AbstractAdminPortlet
{
    use StringTranslationTrait;

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
        return 'admin/dashboard/site/mine';
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
     * {inheritDoc}
     */
    protected function getDisplay(&$query, PageState $pageState)
    {
        $pageState->setSortField('s.ts_changed');

        $query['uid'] = $this->getAccount()->id();

        return new SitePortletDisplay($this->t("No attached site yet."));
    }

    /**
     * Return true if portlet if visible for user.
     *
     * @param $account
     * @return mixed
     */
    public function userIsAllowed(AccountInterface $account)
    {
        return $account->hasPermission(Access::PERM_SITE_REQUEST);
    }
}
