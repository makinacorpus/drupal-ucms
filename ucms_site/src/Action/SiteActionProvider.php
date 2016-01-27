<?php

namespace MakinaCorpus\Ucms\Site\Action;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteAccessService;
use MakinaCorpus\Ucms\Dashboard\Action\ActionSeparator;

class SiteActionProvider implements ActionProviderInterface
{
    /**
     * @var SiteAccessService
     */
    private $access;

    /**
     * Default constructor
     *
     * @param SiteAccessService $access
     */
    public function __construct(SiteAccessService $access)
    {
        $this->access = $access;
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item)
    {
        $ret = [];

        if ($this->access->userCanManage($item)) {
            $ret[] = new Action(t("View"), 'admin/dashboard/site/' . $item->id, null, 'eye-open', -1);
            $ret[] = new Action(t("Edit"), 'admin/dashboard/site/' . $item->id . '/edit', null, 'pencil', 0, true, true);
            $ret[] = new Action(t("History"), 'admin/dashboard/site/' . $item->id . '/log', null, 'list-alt', -1, false);
        }
        // @todo Test all states and permissions
        //  switch site to state STATE when possible (secondary)
        // @todo Consider delete as a state
        if ($this->access->userCanManageWebmasters($item)) {
            $ret[] = new ActionSeparator(0, false);
            $ret[] = new Action(t("Add webmaster"), 'admin/dashboard/site/' . $item->id . '/edit', null, 'user', 1, false, true);
            $ret[] = new Action(t("Manage webmasters"), 'admin/dashboard/site/' . $item->id . '/edit', null, 'user', 2, false, true);
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
