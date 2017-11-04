<?php

namespace MakinaCorpus\Ucms\Group\Action;

use MakinaCorpus\ACL\Permission;
use MakinaCorpus\Calista\Action\Action;
use MakinaCorpus\Ucms\Group\Group;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\Action\AbstractActionProvider;

class GroupActionProvider extends AbstractActionProvider
{
    /**
     * {inheritdoc}
     */
    public function getActions($item, $primaryOnly = false, array $groups = [])
    {
        $ret = [];

        /** @var \MakinaCorpus\Ucms\Group\Group $item */
        $canView = $this->isGranted(Permission::VIEW, $item);

        if ($canView) {
            $ret[] = new Action($this->t("All members"), 'admin/dashboard/group/' . $item->getId() . '/members', [], 'user', 100, false, false, false, 'user');
        }
        if ($this->isGranted(Access::ACL_PERM_MANAGE_USERS, $item)) {
            $ret[] = new Action($this->t("Add existing member"), 'admin/dashboard/group/' . $item->getId() . '/members/add', 'dialog', 'user', 110, false, true, false, 'user');
        }

        if ($canView) {
            $ret[] = new Action($this->t("All sites"), 'admin/dashboard/group/' . $item->getId() . '/sites', [], 'cloud', 200, false, false, false, 'site');
        }
        if ($this->isGranted(Access::ACL_PERM_MANAGE_SITES, $item)) {
            $ret[] = new Action($this->t("Add site"), 'admin/dashboard/group/' . $item->getId() . '/sites/add', 'dialog', 'cloud', 210, false, true, false, 'site');
        }

        if ($canView) {
            $ret[] = new Action($this->t("View"), 'admin/dashboard/group/' . $item->getId(), [], 'eye', 0, true, false, false, 'edit');
        }
        if ($this->isGranted(Permission::UPDATE, $item)) {
            $ret[] = new Action($this->t("Edit"), 'admin/dashboard/group/' . $item->getId() . '/edit', [], 'pencil', 400, false, true, false, 'edit');
        }

        return $ret;
    }

    /**
     * {inheritdoc}
     */
    public function supports($item)
    {
        return $item instanceof Group;
    }
}
