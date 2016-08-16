<?php

namespace MakinaCorpus\Ucms\Group;

/**
 * Single access record for a group and user couple
 *
 * This object is immutable
 *
 * @todo
 *   - reference the site object within
 */
class GroupMember
{
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
