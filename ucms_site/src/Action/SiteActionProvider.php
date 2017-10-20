<?php

namespace MakinaCorpus\Ucms\Site\Action;

use MakinaCorpus\ACL\Permission;
use MakinaCorpus\Calista\Action\Action;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;

class SiteActionProvider extends AbstractActionProvider
{
    /**
     * @var SiteManager
     */
    private $manager;

    /**
     * @var AccountInterface
     */
    private $currentUser;

    /**
     * Default constructor
     *
     * @param SiteManager $manager
     */
    public function __construct(SiteManager $manager)
    {
        $this->manager = $manager;
        // @todo FIXME
        $this->currentUser = \Drupal::currentUser();
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item, $primaryOnly = false, array $groups = [])
    {
        $ret = [];

        $account  = $this->currentUser;
        $access   = $this->manager->getAccess();

        $canOverview    = $this->isGranted(Permission::OVERVIEW, $item);
        $canView        = $this->isGranted(Permission::VIEW, $item);
        $canManage      = $this->isGranted(Permission::UPDATE, $item);
        $canManageUsers = $this->isGranted(Access::ACL_PERM_SITE_MANAGE_USERS, $item);

        if ($canOverview) {
            $ret[] = new Action($this->t("View"), 'admin/dashboard/site/' . $item->id, null, 'eye', -10);
            // We do not check site state, because if user cannot view site, it
            // should not end up being checked against here (since SQL query
            // alteration will forbid it).
            if ($canView) {
                $uri = $this->manager->getUrlGenerator()->generateUrl($item->id);
                $ret[] = new Action($this->t("Go to site"), $uri, null, 'external-link', -5, true);
            }
            if ($canManage) {
                $ret[] = new Action($this->t("Edit"), 'admin/dashboard/site/' . $item->id . '/edit', null, 'pencil', -2, false, true);
            }
            $ret[] = new Action($this->t("History"), 'admin/dashboard/site/' . $item->id . '/log', null, 'list-alt', -1, false);
        }

        // Append all possible state switch operations
        $i = 10;
        foreach ($access->getAllowedTransitions($account, $item) as $state => $name) {
            $ret[] = new Action($this->t("Switch to @state", ['@state' => $this->t($name)]), 'admin/dashboard/site/' . $item->id . '/switch/' . $state, 'dialog', 'refresh', ++$i, false, true, false, 'switch');
        }

        // @todo Consider delete as a state
        if ($canManageUsers) {
            // 100 as priority is enough to be number of states there is ($i)
            $ret[] = new Action($this->t("Add existing user"), 'admin/dashboard/site/' . $item->id . '/webmaster/add-existing', 'dialog', 'user', 100, false, true, false, 'user');
            $ret[] = new Action($this->t("Create new user"), 'admin/dashboard/site/' . $item->id . '/webmaster/add-new', null, 'user', 101, false, true, false, 'user');
            $ret[] = new Action($this->t("Manage users"), 'admin/dashboard/site/' . $item->id . '/webmaster', null, 'user', 102, false, false, false, 'user');
        }

        if ($access->userCanDelete($account, $item)) {
            $ret[] = new Action($this->t("Delete"), 'admin/dashboard/site/' . $item->id . '/delete', 'dialog', 'trash', 1000, false, true, false, 'switch');
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
