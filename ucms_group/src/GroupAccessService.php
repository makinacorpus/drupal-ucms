<?php

namespace MakinaCorpus\Ucms\Group;

use Drupal\Core\Session\AccountInterface;

class GroupAccessService
{
    private $database;
    private $storage;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $database
     */
    public function __construct(\DatabaseConnection $database)
    {
        $this->database = $database;
    }

    public function userIsMember(AccountInterface $account, Group $group)
    {
        return true; // @todo fixme
    }

    public function userCanView(AccountInterface $account, Group $group)
    {
        return
            $account->hasPermission(GroupAccess::PERM_VIEW_ALL) ||
            $account->hasPermission(GroupAccess::PERM_MANAGE_ALL) ||
            $this->userIsMember($account, $group)
        ;
    }

    public function userCanEdit(AccountInterface $account, Group $group)
    {
        return $account->hasPermission(GroupAccess::PERM_MANAGE_ALL);
    }

    public function userCanDelete(AccountInterface $account, Group $group)
    {
        return $account->hasPermission(GroupAccess::PERM_MANAGE_ALL);
    }

    public function userCanManageMembers(AccountInterface $account, Group $group)
    {
        return $account->hasPermission(GroupAccess::PERM_MANAGE_ALL);
    }
}
