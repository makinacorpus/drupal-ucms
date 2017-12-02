<?php

namespace MakinaCorpus\Ucms\Group\Action;

use MakinaCorpus\Calista\Action\Action;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\Action\AbstractActionProvider;

class SiteActionProvider extends AbstractActionProvider
{
    /**
     * {inheritdoc}
     */
    public function getActions($item, $primaryOnly = false, array $groups = [])
    {
        /** @var \MakinaCorpus\Ucms\Site\Site $item */
        $ret = [];

        if ($this->isGranted(Access::PERM_GROUP_MANAGE_ALL)) {
            $ret[] = new Action($this->t("Attach to group"), 'admin/dashboard/site/' . $item->getId() . '/group-attach', 'dialog', 'tent', 200, false, true, false, 'group');
        }

        return $ret;
    }

    /**
     * {inheritdoc}
     */
    public function supports($item)
    {
        return $item instanceof Site;
    }
}
