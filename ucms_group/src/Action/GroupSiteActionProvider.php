<?php

namespace MakinaCorpus\Ucms\Group\Action;

use MakinaCorpus\ACL\Permission;
use MakinaCorpus\Calista\Action\AbstractActionProvider;
use MakinaCorpus\Calista\Action\Action;
use MakinaCorpus\Ucms\Group\GroupSite;
use MakinaCorpus\Ucms\Site\SiteManager;

/**
 * We only partially implement the site action provider, we do not want to
 * display irrelevant information in contextual actions
 */
class GroupSiteActionProvider extends AbstractActionProvider
{
    private $siteManager;

    /**
     * Default constructor
     *
     * @param SiteManager $siteManager
     */
    public function __construct(SiteManager $siteManager)
    {
        $this->siteManager = $siteManager;
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item, $primaryOnly = false, array $groups = [])
    {
        $ret = [];

        /** @var \MakinaCorpus\Ucms\Group\GroupSite $item */
        $site = $item->getSite();

        if ($this->isGranted(Permission::OVERVIEW, $site)) {
            $ret[] = new Action($this->t("View"), 'admin/dashboard/site/' . $site->getId(), null, 'eye-open', -10);
        }

        return $ret;
    }

    /**
     * {inheritdoc}
     */
    public function supports($item)
    {
        return $item instanceof GroupSite;
    }
}
