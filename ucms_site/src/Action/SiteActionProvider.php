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
            $ret[] = new Action($this->t("View"), 'admin/dashboard/site/' . $item->id, null, 'eye-open', -10);
            $ret[] = new Action($this->t("Edit"), 'admin/dashboard/site/' . $item->id . '/edit', null, 'pencil', -5, true, true);
            if ($this->access->userCanView($item)) {
              $ret[] = new Action($this->t("Got to site"), url('http://' . $item->http_host), null, 'share-alt', -2, false);
            }
            $ret[] = new Action($this->t("History"), 'admin/dashboard/site/' . $item->id . '/log', null, 'list-alt', -1, false);
        }

        // Append all possible state switch operations
        $i = 10;
        foreach ($this->access->getAllowedTransitions($item) as $state => $name) {
            $ret[] = new Action($this->t("Switch to @state", ['@state' => $name]), 'admin/dashboard/site/' . $item->id . '/switch/' . $state, 'dialog', 'refresh', ++$i, false, true);
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
