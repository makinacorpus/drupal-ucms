<?php

namespace MakinaCorpus\Ucms\User\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;

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
        if (method_exists($item, 'getUserId')) {
            return $item->getUserId();
        }
        if (property_exists($item, 'uid')) {
            return $item->uid;
        }
        if (property_exists($item, 'user_id')) {
            return $item->user_id;
        }
        if (method_exists($item, 'id')) {
            return $item->id();
        }
        throw new \InvalidArgumentException("cannot find user identifier from item");
    }

    private function getStatusFrom($item)
    {
        if (method_exists($item, 'isActive')) {
            return $item->isActive();
        }
        if (property_exists($item, 'isBlocked')) {
            return !$item->isBlocked();
        }
        if (property_exists($item, 'status')) {
            return $item->status;
        }
        throw new \InvalidArgumentException("cannot find user status from item");
    }

    /**
     * {@inheritdoc}
     */
    public function getActions($item)
    {
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
        return is_object($item) && property_exists($item, 'uid') && property_exists($item, 'mail');
    }
}
