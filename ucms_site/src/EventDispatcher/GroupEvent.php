<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use MakinaCorpus\Ucms\Site\Group;
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
     */
    public function __construct(Group $group, $userId = null, array $arguments = [])
    {
        $arguments['uid'] = $userId;

        parent::__construct($group, $arguments);
    }

    /**
     * Who did this
     */
    public function getUserId(): int
    {
        return (int)$this->getArgument('uid');
    }

    /**
     * Get group
     */
    public function getGroup(): Group
    {
        return $this->getSubject();
    }
}
