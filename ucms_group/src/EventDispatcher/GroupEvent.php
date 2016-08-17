<?php

namespace MakinaCorpus\Ucms\Group\EventDispatcher;

use MakinaCorpus\Ucms\Group\Group;

use Symfony\Component\EventDispatcher\GenericEvent;

class GroupEvent extends GenericEvent
{
    const EVENT_CREATE = 'group:create';
    const EVENT_DELETE = 'group:delete';
    const EVENT_PRECREATE = 'group:preCreate';
    const EVENT_PREDELETE = 'group:preDelete';
    const EVENT_PRESAVE = 'group:preSave';
    const EVENT_SAVE = 'group:save';

    /**
     * Default constructor
     *
     * @param Group $group
     * @param int $userId
     * @param array $arguments
     */
    public function __construct(Group $group, $userId = null, array $arguments = [])
    {
        $arguments['uid'] = $userId;

        parent::__construct($group, $arguments);
    }

    /**
     * Who did this
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->getArgument('uid');
    }

    /**
     * Get group
     *
     * @return Group
     */
    public function getGroup()
    {
        return $this->getSubject();
    }
}
