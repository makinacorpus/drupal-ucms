<?php

namespace MakinaCorpus\Ucms\Group;

use MakinaCorpus\Ucms\Site\Structure\PartialUserInterface;
use MakinaCorpus\Ucms\Site\Structure\PartialUserTrait;
use MakinaCorpus\Ucms\Site\Access;

/**
 * Single access record for a group and user couple
 *
 * This object is immutable
 */
class GroupMember implements GroupAwareInterface, PartialUserInterface
{
    use PartialUserTrait;

    /**
     * Use this only when you can't or don't want to go throught database
     *
     * @param int $groupId
     * @param int $userId
     * @param int $role
     */
    static public function create($groupId, $userId, $role)
    {
        $instance = new self();

        $instance->group_id = (int)$groupId;
        $instance->user_id = (int)$userId;
        $instance->role = (int)$role;

        return $instance;
    }

    /**
     * @var int
     */
    private $group_id;

    /**
     * @var int
     */
    private $user_id;

    /**
     * @var int
     */
    private $role;

    /**
     * {@inheritdoc}
     */
    public function getUserId()
    {
        return (int)$this->user_id;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroupId()
    {
        return (int)$this->group_id;
    }

    /**
     * Get roles bitmask
     *
     * @return int
     */
    public function getRoleMask()
    {
        return (int)$this->role;
    }

    /**
     * Is user group admin
     */
    public function isGroupAdmin()
    {
        return $this->role & Access::ROLE_GROUP_ADMIN;
    }
}
