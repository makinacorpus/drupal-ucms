<?php

namespace MakinaCorpus\Ucms\Site\Action;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteAccessService;
use MakinaCorpus\Ucms\Dashboard\Action\ActionSeparator;

class SiteActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;

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
            $ret[] = new Action($this->t("View"), 'admin/dashboard/site/' . $item->id, null, 'eye-open', -1);
            $ret[] = new Action($this->t("Edit"), 'admin/dashboard/site/' . $item->id . '/edit', null, 'pencil', 0, true, true);
            $ret[] = new Action($this->t("History"), 'admin/dashboard/site/' . $item->id . '/log', null, 'list-alt', -1, false);
        }
        // @todo Test all states and permissions
        //  switch site to state STATE when possible (secondary)

        $i = 10;
        foreach ($this->access->getAllowedTransitions($item) as $state => $name) {
            $ret[] = new Action($this->t("Switch to @state", ['@state' => $name]), 'admin/dashboard/site/' . $item->id . '/switch/' . $state, null, 'refresh', ++$i, false, true);
        }

        // @todo Consider delete as a state
        if ($this->access->userCanManageWebmasters($item)) {
            $ret[] = new ActionSeparator(0, false);
            // 100 as priority is enough to be number of states there is ($i)
            $ret[] = new Action($this->t("Add webmaster"), 'admin/dashboard/site/' . $item->id . '/edit', null, 'user', 100, false, true);
            $ret[] = new Action($this->t("Manage webmasters"), 'admin/dashboard/site/' . $item->id . '/edit', null, 'user', 101, false, true);
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
