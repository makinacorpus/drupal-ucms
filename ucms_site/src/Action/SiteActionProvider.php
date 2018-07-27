<?php

namespace MakinaCorpus\Ucms\Site\Action;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\Action\AbstractActionProvider;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;

class SiteActionProvider extends AbstractActionProvider
{
    use StringTranslationTrait;
    use UrlGeneratorTrait;

    private $manager;
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
    public function getActions($item, bool $primaryOnly = false, array $groups = []): array
    {
        $ret = [];

        $account  = $this->currentUser;
        $access   = $this->manager->getAccess();

        if ($access->userCanOverview($account, $item)) {
            $ret[] = new Action($this->t("View"), 'ucms_site.admin.site.view', ['site' => $item->id], 'eye-open', -10);
            // We do not check site state, because if user cannot view site, it
            // should not end up being checked against here (since SQL query
            // alteration will forbid it).
            if ($access->userCanView($account, $item)) {
                // $uri = $this->url('<front>', [], ['ucms_site' => $item->id]); // @todo
                $ret[] = new Action($this->t("Go to site"), 'ucms_site.admin.site.view', ['site' => $item->id], 'share-alt', -5, true);
            }
            if ($access->userCanManage($account, $item)) {
                $ret[] = new Action($this->t("Edit"), 'ucms_site.admin.site.view', ['site' => $item->id], 'pencil', -2, false, true);
            }
            if ($account->hasPermission(Access::PERM_SITE_MANAGE_HOSTNAME)) {
                $ret[] = new Action($this->t("Change hostname"), 'ucms_site.admin.site.view', ['site' => $item->id], 'fire', 0, false, true);
            }
            //$ret[] = new Action($this->t("History"), 'ucms_site.admin.site.view', ['site' => $item->id], 'list-alt', -1, false);
        }

        // Append all possible state switch operations
        $i = 10;
        foreach ($access->getAllowedTransitions($account, $item) as $state => $name) {
            $ret[] = new Action($this->t("Switch to @state", ['@state' => $this->t($name)]), 'ucms_site.admin.site.view', ['site' => $item->id, 'dialog' => true], 'refresh', ++$i, false, true, false, 'switch');
        }

        // @todo Consider delete as a state
        /*
         * FIXME implement me
         *
        if ($access->userCanManageWebmasters($account, $item)) {
            // 100 as priority is enough to be number of states there is ($i)
            $ret[] = new Action($this->t("Add existing user"), 'admin/dashboard/site/' . $item->id . '/webmaster/add-existing', 'dialog', 'user', 100, false, true, false, 'user');
            $ret[] = new Action($this->t("Create new user"), 'admin/dashboard/site/' . $item->id . '/webmaster/add-new', null, 'user', 101, false, true, false, 'user');
            $ret[] = new Action($this->t("Manage users"), 'admin/dashboard/site/' . $item->id . '/webmaster', null, 'user', 102, false, false, false, 'user');
        }
         */

        if ($access->userCanDelete($account, $item)) {
            $ret[] = new Action($this->t("Delete"), 'ucms_site.admin.site.view', ['site' => $item->id, 'dialog' => true], 'trash', 1000, false, true, false, 'switch');
        }

        return $ret;
    }

    /**
     * {inheritdoc}
     */
    public function supports($item): bool
    {
        return $item instanceof Site;
    }
}
