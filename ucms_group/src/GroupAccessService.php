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

    /**
     * Is user member of the given group
     *
     * @param AccountInterface $account
     * @param Group $group
     *
     * @return bool
     */
    public function userIsMember(AccountInterface $account, Group $group)
    {
        return true; // @todo fixme
    }

    /**
     * Get user groups
     *
     * @param AccountInterface $account
     *
     * @return GroupMember[]
     */
    public function getUserGroups(AccountInterface $account)
    {
        throw new \Exception("Not implemented yet");
    }

    /**
     * Add member to group
     *
     * @param int $groupId
     * @param int $userId
     *
     * @return GroupMember
     */
    public function addMember($groupId, $userId)
    {
        $this
            ->database
            ->merge('ucms_group_user')
            ->key([
                'group_id'  => $groupId,
                'user_id'   => $userId,
            ])
            ->execute()
        ;

        return GroupMember::create($groupId, $userId);
    }

    /**
     * Remove member from group
     *
     * If association does not exists, this will silently do nothing
     *
     * @param int $groupId
     * @param int $userId
     */
    public function removeMember($groupId, $userId)
    {
        $this
            ->database
            ->delete('ucms_group_user')
            ->condition('group_id', $groupId)
            ->condition('user_id', $userId)
            ->execute()
        ;
    }

    /**
     * Can user view the group details
     *
     * @param AccountInterface $account
     * @param Group $group
     *
     * @return bool
     */
    public function userCanView(AccountInterface $account, Group $group)
    {
        return
            $account->hasPermission(GroupAccess::PERM_VIEW_ALL) ||
            $account->hasPermission(GroupAccess::PERM_MANAGE_ALL) ||
            $this->userIsMember($account, $group)
        ;
    }

    /**
     * Can user edit the group details
     *
     * @param AccountInterface $account
     * @param Group $group
     *
     * @return bool
     */
    public function userCanEdit(AccountInterface $account, Group $group)
    {
        return $account->hasPermission(GroupAccess::PERM_MANAGE_ALL);
    }

    /**
     * Can user delete the group
     *
     * @param AccountInterface $account
     * @param Group $group
     *
     * @return bool
     */
    public function userCanDelete(AccountInterface $account, Group $group)
    {
        return $account->hasPermission(GroupAccess::PERM_MANAGE_ALL);
    }

    /**
     * Can user manage the group members
     *
     * @param AccountInterface $account
     * @param Group $group
     *
     * @return bool
     */
    public function userCanManageMembers(AccountInterface $account, Group $group)
    {
        return $account->hasPermission(GroupAccess::PERM_MANAGE_ALL);
    }
}
