<?php

namespace MakinaCorpus\Ucms\User\EventDispatcher;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\Event;

class UserAccessEvent extends Event
{
    const EVENT_NAME = 'ucms_user:access';

    private $account;
    private $denied = 0;
    private $granted = 0;
    private $operation;
    private $target;

    /**
     * Default constructor
     */
    public function __construct(AccountInterface $target, AccountInterface $account, $operation = 'view')
    {
        $this->target = $target;
        $this->account = $account;
        $this->operation  = $operation;
    }

    /**
     * Get operation
     *
     * @return string
     *   'delete', 'update' or 'view'
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * Get user account (the one who do the action)
     *
     * @return AccountInterface
     */
    public function getUserAccount()
    {
        return $this->account;
    }

    /**
     * Get user account target (the one we check access upon)
     *
     * @return AccountInterface
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Deny (this is definitive).
     */
    public function deny()
    {
        ++$this->denied;
    }

    /**
     * Grant user (if someone explicitely denied, it will remain denied).
     */
    public function allow()
    {
        ++$this->granted;
    }

    /**
     * User is allowed by listeners
     *
     * @return bool
     */
    public function isGranted()
    {
        // @todo for now this is a deny only event, in 2.x everything will be implemented using this
        return !$this->denied  /* && $this->granted */;
    }
}
