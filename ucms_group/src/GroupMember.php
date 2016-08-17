<?php

namespace MakinaCorpus\Ucms\Group;

use MakinaCorpus\Ucms\Site\Structure\PartialUserTrait;

/**
 * Single access record for a group and user couple
 *
 * This object is immutable
 */
class GroupMember
{
    use PartialUserTrait;

    /**
     * Use this only when you can't or don't want to go throught database
     *
     * @param int $groupId
     * @param int $userId
     */
    static public function create($groupId, $userId)
    {
        $instance = new self();

        $instance->group_id = $groupId;
        $instance->user_id = $userId;

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
     * Get user identifier
     *
     * @return int
     */
    public function getUserId()
    {
        return (int)$this->user_id;
    }

    /**
     * Get group identifier
     *
     * @return int
     */
    public function getGroupId()
    {
        return (int)$this->group_id;
    }
}
