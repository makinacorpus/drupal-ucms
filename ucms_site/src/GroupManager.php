<?php

namespace MakinaCorpus\Ucms\Site;

use Drupal\Core\Session\AccountInterface;
use MakinaCorpus\Ucms\Site\Error\GroupMoveDisallowedException;
use MakinaCorpus\Ucms\Site\EventDispatcher\GroupEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class GroupManager
{
    private $accessCache = [];
    private $database;
    private $dispatcher;

    /**
     * Default constructor
     */
    public function __construct(\DatabaseConnection $database, EventDispatcherInterface $dispatcher = null)
    {
        $this->database = $database;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Dispatch an event
     *
     * @param Group $group
     * @param string $event
     * @param string $data
     * @param int $userId
     */
    private function dispatch(Group $group, $event, $data = [], $userId = null)
    {
        if (!$this->dispatcher) {
            return;
        }

        $this->dispatcher->dispatch('group:' . $event, new GroupEvent($group, $userId, $data));
    }

    /**
     * Load groups from
     *
     * @param int[]|GroupSite|GroupMember[] $items
     * @param bool $withAccess
     *
     * @return Group[]
     */
    public function loadGroupsFrom(array $items = [], $withAccess = false)
    {
        $idList = [];

        foreach ($items as $item) {
            if (is_numeric($item)) {
                $groupId = (int)$item;
            } else if ($item instanceof GroupMember) {
                $groupId = $item->getGroupId();
            } else if ($item instanceof GroupSite) {
                $groupId = $item->getGroupId();
            } else {
                throw new \InvalidArgumentException(sprintf("given input is nor an integer nor a %s nor %s instance", GroupMember::class, GroupSite::class));
            }
            // Avoid duplicates
            $idList[$groupId] = $groupId;
        }

        if (!$idList) {
            return [];
        }

        return $this->loadAll($idList, $withAccess);
    }

    /**
     * Load group by identifier
     *
     * @param int $id
     *
     * @return Group
     *
     * @throws \InvalidArgumentException
     */
    public function findOne($id)
    {
        $group = $this
            ->database
            ->query(
                "SELECT * FROM {ucms_group} WHERE id = :id LIMIT 1 OFFSET 0",
                [':id' => $id]
            )
            ->fetchObject(Group::class)
        ;

        if (!$group) {
            throw new \InvalidArgumentException("Group does not exists");
        }

        return $group;
    }

    /**
     * Load all groups from the given identifiers
     *
     * @param array $idList
     * @param string $withAccess
     *
     * @return Group[]
     */
    public function loadAll($idList = [], $withAccess = true)
    {
        $ret = [];

        if (empty($idList)) {
            return $ret;
        }

        $q = $this
            ->database
            ->select('ucms_group', 'g')
        ;

        if ($withAccess) {
            $q->addTag('ucms_group_access');
        }

        $groups = $q
            ->fields('g')
            ->condition('g.id', $idList)
            ->execute()
            ->fetchAll(\PDO::FETCH_CLASS, Group::class)
        ;

        // Ensure order is the same
        // FIXME: find a better way
        $sort = [];
        foreach ($groups as $group) {
            $sort[$group->getId()] = $group;
        }
        foreach ($idList as $id) {
            if (isset($sort[$id])) {
                $ret[$id] = $sort[$id];
            }
        }

        return $ret;
    }

    /**
     * Save given group
     *
     * If the given group has no identifier, its identifier will be set
     *
     * @param Group $group
     * @param array $fields
     *   If set, update only the given fields
     * @param int $userId
     *   Who did this!
     */
    public function save(Group $group, array $fields = null, $userId = null)
    {
        $eligible = [
            'title',
            'is_ghost',
            'is_meta',
            'ts_created',
            'ts_changed',
            'attributes',
        ];

        if (null === $fields) {
            $fields = $eligible;
        } else {
            $fields = array_intersect($eligible, $fields);
        }

        $values = [];
        foreach ($fields as $field) {
            switch ($field) {

                case 'attributes':
                    $attributes = $group->getAttributes();
                    if (empty($attributes)) {
                        $values[$field] = null;
                    } else {
                        $values[$field] = serialize($attributes);
                    }
                    break;

                case 'is_ghost':
                    $values['is_ghost'] = (int)$group->isGhost();
                    break;

                case 'is_meta':
                    $values['is_meta'] = (int)$group->isMeta();
                    break;

                default:
                    $values[$field] = $group->{$field}; // @todo uses __get() fixme
            }
        }

        $values['ts_changed'] = $group->touch()->format('Y-m-d H:i:s');

        if ($group->getId()) {
            $this->dispatch($group, 'preSave', [], $userId);

            $this
                ->database
                ->merge('ucms_group')
                ->key(['id' => $group->getId()])
                ->fields($values)
                ->execute()
            ;

            $this->dispatch($group, 'save', [], $userId);
        } else {
            $this->dispatch($group, 'preCreate', [], $userId);

            $values['ts_created'] = $values['ts_changed'];

            $id = $this
                ->database
                ->insert('ucms_group')
                ->fields($values)
                ->execute()
            ;

            $group->setId($id);

            $this->dispatch($group, 'create', [], $userId);
        }
    }

    /**
     * Delete the given group
     *
     * @param Group $group
     * @param int $userId
     *   Who did this!
     */
    public function delete(Group $group, $userId = null)
    {
        $this->dispatch($group, 'preDelete', [], $userId);

        $this->database->delete('ucms_group')->condition('id', $group->getId())->execute();

        $this->dispatch($group, 'delete', [], $userId);
    }

    /**
     * Touch (flag as modified, no other modifications) a group
     *
     * @param int $groupId
     */
    public function touch($groupId)
    {
        $now = new \DateTime();

        $this
            ->database
            ->query(
                "UPDATE {ucms_group} SET ts_changed = :time WHERE id = :id",
                [':time' => $now->format('Y-m-d H:i:s'), ':id' => $groupId]
            )
        ;
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
            return $this->findOne($groupId);
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

        $this->touch($groupId);

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

        $this->touch($groupId);
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

        $this->touch($groupId);

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

        $this->touch($groupId);

        $this->resetCache();
    }
}
