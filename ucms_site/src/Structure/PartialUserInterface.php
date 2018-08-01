<?php

namespace MakinaCorpus\Ucms\Site\Structure;

/**
 * Type hint implementations of the PartialUserTrait with this interface to
 * benefit from automatic features, such as dashboard actions
 */
interface PartialUserInterface
{
    /**
     * Get user identifier
     */
    public function getUserId(): int;

    /**
     * Get user unescaped account name
     */
    public function getAccountName(): string;

    /**
     * Get user escaped display name
     */
    public function getDisplayName(): string;

    /**
     * Get user email
     */
    public function getEmail(): string;

    /**
     * Is this user active
     */
    public function isActive(): bool;

    /**
     * Is this user blocked
     */
    public function isBlocked(): bool;
}
