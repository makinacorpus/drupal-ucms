<?php

namespace MakinaCorpus\Ucms\Group\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Site\Group;
use MakinaCorpus\Ucms\Site\GroupManager;

class GroupActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;

    private $groupManager;
    private $currentUser;

    /**
     * Default constructor
     *
     * @param GroupManager $groupManager
     * @param AccountInterface $currentUser
     */
    public function __construct(GroupManager $groupManager, AccountInterface $currentUser)
    {
        $this->groupManager = $groupManager;
        $this->currentUser = $currentUser;
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item)
    {
        $ret = [];

        /** @var \MakinaCorpus\Ucms\Site\Group $item */
        $canView = $this->groupManager->userCanView($this->currentUser, $item);

        if ($canView) {
            $ret[] = new Action($this->t("All members"), 'admin/dashboard/group/' . $item->getId() . '/members', [], 'user', 100, false, false, false, 'user');
        }
        if ($this->groupManager->userCanManageMembers($this->currentUser, $item)) {
            $ret[] = new Action($this->t("Add existing member"), 'admin/dashboard/group/' . $item->getId() . '/members/add', 'dialog', 'user', 110, false, true, false, 'user');
        }

        if ($canView) {
            $ret[] = new Action($this->t("All sites"), 'admin/dashboard/group/' . $item->getId() . '/sites', [], 'cloud', 200, false, false, false, 'site');
        }
        if ($this->groupManager->userCanManageSites($this->currentUser, $item)) {
            $ret[] = new Action($this->t("Add site"), 'admin/dashboard/group/' . $item->getId() . '/sites/add', 'dialog', 'cloud', 210, false, true, false, 'site');
        }

        if ($canView) {
            $ret[] = new Action($this->t("View"), 'admin/dashboard/group/' . $item->getId(), [], 'eye-open', 0, true, false, false, 'edit');
        }
        if ($this->groupManager->userCanEdit($this->currentUser, $item)) {
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
