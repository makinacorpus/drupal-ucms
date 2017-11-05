<?php

namespace MakinaCorpus\Ucms\Group;

use Drupal\Core\Session\AccountInterface;
use MakinaCorpus\Ucms\Group\Error\GroupMoveDisallowedException;
use MakinaCorpus\Ucms\Site\Site;

class GroupAccessService
{
    private $database;
    private $storage;
    private $accessCache = [];

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $database
     */
    public function __construct(\DatabaseConnection $database, GroupStorage $storage)
    {
        $this->database = $database;
        $this->storage = $storage;
    }

    /**
     * Reset user access cache
     */
    public function resetCache()
    {
        $this->accessCache = [];
    }

    /**
     * Get site groups
     *
     * @param Site $site
     *
     * @return Group
     */
    public function getSiteGroup(Site $site)
    {
        $groupId = $site->getGroupId();

        if ($groupId) {
            return $this->storage->findOne($groupId);
        }
    }

    /**
     * Add site to group
     *
     * @param int $groupId
     * @param int $siteId
     * @param boolean $allowChange
     *   If set to false and site does already belong to a group, throw
     *   an exception
     *
     * @return bool
     *   True if user was really added, false if site is already in group
     */
    public function addSite($groupId, $siteId, $allowChange = false)
    {
        $ret = false;

        $currentGroupId = (int)$this
            ->database
            ->query(
                "SELECT group_id FROM {ucms_site} WHERE id = :site",
                [':site' => $siteId]
            )
            ->fetchField()
        ;

        if (!$allowChange && $currentGroupId && $currentGroupId !== (int)$groupId) {
            throw new GroupMoveDisallowedException("site group change is not allowed");
        }

        if ($currentGroupId !== (int)$groupId) {
            $this
                ->database
                ->query(
                    "UPDATE {ucms_site} SET group_id = :group WHERE id = :site",
                    [':site' => $siteId, ':group' => $groupId]
                )
            ;

            $ret = true;
        }

        $this->storage->touch($groupId);

        // @todo dispatch event

        return $ret;
    }

    /**
     * Remote site from group
     *
     * @param int $groupId
     * @param int $siteId
     */
    public function removeSite($groupId, $siteId)
    {
        $currentGroupId = (int)$this
            ->database
            ->query(
                "SELECT group_id FROM {ucms_site} WHERE id = :site",
                [':site' => $siteId]
            )
            ->fetchField()
        ;

        if ($currentGroupId !== (int)$groupId) {
            throw new GroupMoveDisallowedException(sprintf("%s site is not in group %s", $siteId, $groupId));
        }

        $this
            ->database
            ->query(
                "UPDATE {ucms_site} SET group_id = NULL WHERE id = :site",
                [':site' => $siteId]
            )
        ;

        // @todo dispatch event

        $this->storage->touch($groupId);
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
                "SELECT 1 FROM {ucms_group_access} WHERE group_id = :group AND user_id = :user",
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
        $userId = $account->id();

        if (!isset($this->accessCache[$userId])) {
            $this->accessCache[$userId] = [];

            $q = $this
                ->database
                ->select('ucms_group_access', 'gu')
                ->fields('gu', ['group_id', 'user_id', 'role'])
                ->condition('gu.user_id', $userId)
            ;

            // This will populate the PartialUserInterface information without
            // the need to join on the user table. Performance for the win.
            // This will always remain true as long as we have a foreign key
            // constraint on the user table, we are sure that the user actually
            // exists, and since we have the instance, it's all good!
            $q->addExpression(':name', 'name', [':name' => $account->getAccountName()]);
            $q->addExpression(':mail', 'mail', [':mail' => $account->getEmail()]);
            $q->addExpression(':status', 'status', [':status' => $account->status]);

            $r = $q->execute();
            $r->setFetchMode(\PDO::FETCH_CLASS, GroupMember::class);

            // Can't use fetchAllAssoc() because properties are private on the
            // objects built by PDO
            $this->accessCache[$userId] = [];

            foreach ($r as $record) {
                $this->accessCache[$userId][] = $record;
            }
        }

        return $this->accessCache[$userId];
    }

    /**
     * Add member to group
     *
     * @param int $groupId
     * @param int $userId
     *
     * @return bool
     *   True if user was really added, false if user is already a member
     */
    public function addMember($groupId, $userId)
    {
        $exists = (bool)$this
            ->database
            ->query(
                "SELECT 1 FROM {ucms_group_access} WHERE group_id = :group AND user_id = :user",
                [':group' => $groupId, ':user' => $userId]
            )
            ->fetchField()
        ;

        if ($exists) {
            return false;
        }

        $this
            ->database
            ->merge('ucms_group_access')
            ->key([
                'group_id'  => $groupId,
                'user_id'   => $userId,
            ])
            ->execute()
        ;

        // @todo dispatch event

        $this->storage->touch($groupId);

        $this->resetCache();

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
            ->delete('ucms_group_access')
            ->condition('group_id', $groupId)
            ->condition('user_id', $userId)
            ->execute()
        ;

        // @todo dispatch event

        $this->storage->touch($groupId);

        $this->resetCache();
    }
}
