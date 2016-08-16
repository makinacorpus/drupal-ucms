<?php

namespace MakinaCorpus\Ucms\Group\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Group\Group;
use MakinaCorpus\Ucms\Group\GroupManager;

class GroupActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;

    private $groupManager;
    private $currentUser;

    /**
     * Default constructor
     *
     * @param SeoService $service
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

        /** @var \MakinaCorpus\Ucms\Group\Group $item */
        if ($this->groupManager->getAccess()->userCanManageMembers($this->currentUser, $item)) {
            $ret[] = new Action($this->t("Manage members"), 'admin/dashboard/group/' . $item->getId() . '/members', [], 'user', 100, true, false, false, 'user');
            $ret[] = new Action($this->t("Add existing member"), 'admin/dashboard/group/' . $item->getId() . '/members/add', 'dialog', 'user', 110, false, true, false, 'user');
        }

        if ($this->groupManager->getAccess()->userCanView($this->currentUser, $item)) {
            $ret[] = new Action($this->t("View"), 'admin/dashboard/group/' . $item->getId(), [], 'eye-open', 0, true, false, false, 'edit');
        }
        if ($this->groupManager->getAccess()->userCanEdit($this->currentUser, $item)) {
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
