<?php

namespace MakinaCorpus\Ucms\User\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Calista\Action\Action;
use MakinaCorpus\Calista\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Site\Structure\PartialUserInterface;
use MakinaCorpus\Ucms\User\UserAccess;

class UserActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;

    /**
     * @var AccountInterface
     */
    private $currentUser;

    /**
     * Default constructor
     *
     * @param AccountInterface $currentUser
     */
    public function __construct(AccountInterface $currentUser)
    {
        $this->currentUser = $currentUser;
    }

    private function getUserIdFrom($item)
    {
        if ($item instanceof AccountInterface) {
            return $item->id();
        }
        if ($item instanceof PartialUserInterface) {
            return $item->getUserId();
        }
        throw new \InvalidArgumentException("cannot find user identifier from item");
    }

    private function getStatusFrom($item)
    {
        if ($item instanceof AccountInterface) {
            return $item->status;
        }
        if ($item instanceof PartialUserInterface) {
            return $item->isActive();
        }
        throw new \InvalidArgumentException("cannot find user status from item");
    }

    /**
     * {@inheritdoc}
     */
    public function getActions($item, $primaryOnly = false, array $groups = [])
    {
        if (!$this->currentUser->hasPermission(UserAccess::PERM_MANAGE_ALL)) {
            return [];
        }

        $actions = [];

        $userId = $this->getUserIdFrom($item);
        $userStatus = $this->getStatusFrom($item);

        $actions[] = new Action($this->t("View"), 'admin/dashboard/user/' . $userId, null, 'eye-open', 1, true, true);

        if (!$userStatus) {
            $action_title = $this->t("Enable");
            $action_path  = 'admin/dashboard/user/' . $userId . '/enable';
            $action_icon  = 'ok-circle';
        } else {
            $action_title = $this->t("Disable");
            $action_path  = 'admin/dashboard/user/' . $userId . '/disable';
            $action_icon  = 'ban-circle';
        }

        $action_disabled  = ($userId === $this->currentUser->id());
        $actions[] = new Action($action_title, $action_path, 'dialog', $action_icon, 2, false, true, $action_disabled);

        $actions[] = new Action($this->t("Edit"), 'admin/dashboard/user/' . $userId . '/edit', null, 'pencil', 3, false, true);
        $actions[] = new Action($this->t("Change email"), 'admin/dashboard/user/' . $userId . '/change-email', 'dialog', 'pencil', 4, false, true);
        $actions[] = new Action($this->t("Reset password"), 'admin/dashboard/user/' . $userId . '/reset-password', 'dialog', 'refresh', 5, false, true);
        $actions[] = new Action($this->t("Delete"), 'admin/dashboard/user/' . $userId . '/delete', 'dialog', 'trash', 6, false, true);

        return $actions;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($item)
    {
        return $item instanceof AccountInterface || $item instanceof PartialUserInterface;
    }
}
