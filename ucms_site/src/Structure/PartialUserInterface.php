<?php

namespace MakinaCorpus\Ucms\Site\Structure;

/**
 * Type hint implementations of the PartialUserTrait with this interface to
 * benefit from automatic features, such as calista actions
 */
interface PartialUserInterface
{
    /**
     * Get user identifier
     *
     * @return int
     */
    public function getUserId();

    /**
     * Get user unescaped account name
     *
     * @return string
     */
    public function getAccountName();

    /**
     * Get user escaped display name
     *
     * @return string
     */
    public function getDisplayName();

    /**
     * Get user email
     *
     * @return string
     */
    public function getEmail();

    /**
     * Is this user active
     *
     * @return bool
     */
    public function isActive();

    /**
     * Is this user blocked
     *
     */
    public function isBlocked();
}
