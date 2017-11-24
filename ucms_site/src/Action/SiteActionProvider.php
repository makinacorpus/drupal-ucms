<?php

namespace MakinaCorpus\Ucms\Site\Action;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;

class SiteActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;

    /**
     * @var SiteManager
     */
    private $manager;

    /**
     * @var boolean
     */
    private $ssoEnabled = false;

    /**
     * @var \Drupal\Core\Session\AccountInterface
     */
    private $currentUser;

    /**
     * Default constructor
     *
     * @param SiteManager $manager
     */
    public function __construct(SiteManager $manager, ModuleHandlerInterface $moduleHandler = null)
    {
        $this->manager = $manager;
        $this->ssoEnabled = $moduleHandler ? $moduleHandler->moduleExists('ucms_sso') : false;
        // @todo FIXME
        $this->currentUser = \Drupal::currentUser();
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item)
    {
        $ret = [];

        $account  = $this->currentUser;
        $access   = $this->manager->getAccess();

        if ($access->userCanOverview($account, $item)) {
            $ret[] = new Action($this->t("View"), 'admin/dashboard/site/' . $item->id, null, 'eye-open', -10);
            // We do not check site state, because if user cannot view site, it
            // should not end up being checked against here (since SQL query
            // alteration will forbid it).
            if ($access->userCanView($account, $item)) {
                if ($this->ssoEnabled) {
                    $uri = url('sso/goto/' . $item->id);
                } else {
                    $uri = url('http://' . $item->http_host);
                }
                $ret[] = new Action($this->t("Go to site"), $uri, null, 'share-alt', -5, true);
            }
            if ($access->userCanManage($account, $item)) {
                $ret[] = new Action($this->t("Edit"), 'admin/dashboard/site/' . $item->id . '/edit', null, 'pencil', -2, false, true);
            }
            if ($account->hasPermission(Access::PERM_SITE_MANGE_HOSTNAME)) {
                $ret[] = new Action($this->t("Change hostname"), 'admin/dashboard/site/' . $item->id . '/change-hostname', null, 'fire', 0, false, true);
            }
            $ret[] = new Action($this->t("History"), 'admin/dashboard/site/' . $item->id . '/log', null, 'list-alt', -1, false);
        }

        // Append all possible state switch operations
        $i = 10;
        foreach ($access->getAllowedTransitions($account, $item) as $state => $name) {
            $ret[] = new Action($this->t("Switch to @state", ['@state' => $this->t($name)]), 'admin/dashboard/site/' . $item->id . '/switch/' . $state, 'dialog', 'refresh', ++$i, false, true, false, 'switch');
        }

        // @todo Consider delete as a state
        if ($access->userCanManageWebmasters($account, $item)) {
            // 100 as priority is enough to be number of states there is ($i)
            $ret[] = new Action($this->t("Add existing user"), 'admin/dashboard/site/' . $item->id . '/webmaster/add-existing', 'dialog', 'user', 100, false, true, false, 'user');
            $ret[] = new Action($this->t("Create new user"), 'admin/dashboard/site/' . $item->id . '/webmaster/add-new', null, 'user', 101, false, true, false, 'user');
            $ret[] = new Action($this->t("Manage users"), 'admin/dashboard/site/' . $item->id . '/webmaster', null, 'user', 102, false, false, false, 'user');
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
