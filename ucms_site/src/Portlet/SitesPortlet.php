<?php

namespace MakinaCorpus\Ucms\Site\Portlet;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Dashboard\Portlet\AbstractAdminPortlet;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\Site;

class SitesPortlet extends AbstractAdminPortlet
{
    use StringTranslationTrait;

    /**
     * {@inheritDoc}
     */
    public function getTitle()
    {
        return $this->t("Sites");
    }

    /**
     * {@inheritDoc}
     */
    public function getPath()
    {
        return 'admin/dashboard/site';
    }

    /**
     * {inheritDoc}
     */
    protected function getDisplay(&$query, PageState $pageState)
    {
        $pageState->setSortField('s.ts_changed');

        return new SitePortletDisplay($this->t("No site created yet."));
    }

    /**
     * {@inheritDoc}
     */
    public function userIsAllowed(AccountInterface $account)
    {
        return $account->hasPermission(Access::PERM_SITE_MANAGE_ALL);
    }
}
