<?php

namespace MakinaCorpus\Ucms\User\EventDispatcher;

use Symfony\Component\EventDispatcher\GenericEvent;

class UserEvent extends GenericEvent
{
    /**
     * Default constructor
     *
     * @param int $userId
     * @param int $triggerUserId
     * @param array $arguments
     */
    public function __construct($userId, $triggerUserId = null, array $arguments = [])
    {
        $arguments['uid'] = $triggerUserId;

        parent::__construct($userId, $arguments);
    }

    /**
     * Who did this
     *
     * @return int
     */
    public function getUserId()
    {
        return (int)$this->getArgument('uid');
    }

    /**
     * Get the new accound user identifier
     *
     * @return int
     */
    public function getCreatedUserId()
    {
        return (int)$this->getSubject();
    }
}
