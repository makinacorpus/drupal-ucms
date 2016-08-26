<?php

namespace MakinaCorpus\Ucms\Group;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

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
     * @param bool $withAccess
     *
     * @return GroupSite[]
     */
    public function getSiteGroups(Site $site, $withAccess = false)
    {
        $ret = [];

        $q = $this
            ->database
            ->select('ucms_group_site', 'gs')
            ->fields('gs', ['group_id', 'site_id'])
            ->condition('gs.site_id', $site->getId())
            ->groupBy('gs.site_id')
        ;

        if ($withAccess) {
            $q
                ->addTag('ucms_group_access')
                ->addTag('ucms_site_access')
            ;
        }

        $r = $q->execute();
        $r->setFetchMode(\PDO::FETCH_CLASS, GroupSite::class);

        /** @var \MakinaCorpus\Ucms\Group\GroupSite $record */
        foreach ($r as $record) {
            $record->setSite($site);
            $ret[] = $record;
        }

        return $ret;
    }

    /**
     * Get node groups
     *
     * This implementation can only return group identifiers, since sites
     * might have more than one group, case in which duplicates are possible
     * and it is not revelant for node access implementation to know about
     * that.
     *
     * @param NodeInterface $node
     *
     * @return int[]
     */
    public function getNodeGroups(NodeInterface $node)
    {
        if (empty($node->ucms_sites)) {
            return [];
        }

        return $this
            ->database
            ->select('ucms_group_site', 'gs')
            ->fields('gs', ['group_id'])
            ->condition('gs.site_id', $node->ucms_sites)
            ->groupBy('gs.group_id')
            ->execute()
            ->fetchCol()
        ;
    }

    /**
     * Add site to group
     *
     * @param int $groupId
     * @param int $siteId
     *
     * @return bool
     *   True if user was really added, false if site is already in group
     */
    public function addSite($groupId, $siteId)
    {
        $exists = (bool)$this
            ->database
            ->query(
                "SELECT 1 FROM {ucms_group_site} WHERE group_id = :group AND site_id = :site",
                [':group' => $groupId, ':site' => $siteId]
            )
            ->fetchField()
        ;

        if ($exists) {
            return false;
        }

        $this
            ->database
            ->merge('ucms_group_site')
            ->key([
                'group_id'  => $groupId,
                'site_id'   => $siteId,
            ])
            ->execute()
        ;

        $this->storage->touch($groupId);

        // @todo dispatch event

        return true;
    }

    /**
     * Remote site from group
     *
     * @param int $groupId
     * @param int $siteId
     */
    public function removeSite($groupId, $siteId)
    {
        $this
            ->database
            ->delete('ucms_group_site')
            ->condition('group_id', $groupId)
            ->condition('site_id', $siteId)
            ->execute()
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
        $userId = $account->id();

        if (!isset($this->accessCache[$userId])) {
            $this->accessCache[$userId] = [];

            $q = $this
                ->database
                ->select('ucms_group_user', 'gu')
                ->fields('gu', ['group_id', 'user_id'])
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

        $this->storage->touch($groupId);

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

        $this->storage->touch($groupId);
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
     * Can user manage the group sites
     *
     * @param AccountInterface $account
     * @param Group $group
     *
     * @return bool
     */
    public function userCanManageSites(AccountInterface $account, Group $group)
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
