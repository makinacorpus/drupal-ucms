<?php

namespace MakinaCorpus\Ucms\Group\Action;

use MakinaCorpus\ACL\Permission;
use MakinaCorpus\Calista\Action\Action;
use MakinaCorpus\Ucms\Site\GroupSite;
use MakinaCorpus\Ucms\Site\Action\AbstractActionProvider;

/**
 * We only partially implement the site action provider, we do not want to
 * display irrelevant information in contextual actions
 */
class GroupSiteActionProvider extends AbstractActionProvider
{
    /**
     * {inheritdoc}
     */
    public function getActions($item, $primaryOnly = false, array $groups = [])
    {
        $ret = [];

        /** @var \MakinaCorpus\Ucms\Site\GroupSite $item */
        $site = $item->getSite();

        if ($this->isGranted(Permission::OVERVIEW, $site)) {
            $ret[] = new Action($this->t("View"), 'admin/dashboard/site/' . $site->getId(), null, 'eye', -10);
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
