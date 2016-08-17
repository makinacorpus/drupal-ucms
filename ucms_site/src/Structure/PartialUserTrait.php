<?php

namespace MakinaCorpus\Ucms\Site\Structure;

/**
 * Allows some structure to access user identity, in order to provide shortcuts
 * to templates without the need to load data
 */
trait PartialUserTrait
{
    private $name;
    private $mail;
    private $status;

    /**
     * Get user unescaped account name
     */
    public function getAccountName()
    {
        return $this->name;
    }

    /**
     * Get user escaped display name
     *
     * @return string
     */
    public function getDisplayName()
    {
        return check_plain($this->name); // @todo this would need format_username
    }

    /**
     * Get user email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->mail;
    }

    /**
     * Is this user active
     *
     * @return bool
     */
    public function isActive()
    {
        return (bool)$this->status;
    }

    /**
     * Is this user blocked
     *
     */
    public function isBlocked()
    {
        return !$this->status;
    }
}
