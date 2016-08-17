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
        // @todo fix this, cache that
        return (bool)$this
            ->database
            ->query(
                "SELECT 1 FROM {ucms_group_user} WHERE group_id = :group AND user_id = :user",
                [':group' => $group->getId(), ':user' => $account->id()]
            )
            ->fetchField()
        ;
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
     * @return bool
     *   True if user was really added, false if it is already member
     */
    public function addMember($groupId, $userId)
    {
        $exists = (bool)$this
            ->database
            ->query(
                "SELECT 1 FROM {ucms_group_user} WHERE group_id = :group AND user_id = :user",
                [':group' => $groupId, ':user' => $userId]
            )
            ->fetchField()
        ;

        if ($exists) {
            return false;
        }

        $this
            ->database
            ->merge('ucms_group_user')
            ->key([
                'group_id'  => $groupId,
                'user_id'   => $userId,
            ])
            ->execute()
        ;

        // @todo dispatch event

        return true;
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

        // @todo dispatch event
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
        return $this->userCanManageAll($account) || $this->userIsMember($account, $group);
    }

    /**
     * Can user manage all groups
     *
     * @param AccountInterface $account
     *
     * @return bool
     */
    public function userCanManageAll(AccountInterface $account)
    {
        return
            $account->hasPermission(GroupAccess::PERM_VIEW_ALL) ||
            $account->hasPermission(GroupAccess::PERM_MANAGE_ALL)
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
        return $this->userCanManageAll($account);
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
        return $this->userCanManageAll($account);
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
        return $this->userCanManageAll($account);
    }
}
