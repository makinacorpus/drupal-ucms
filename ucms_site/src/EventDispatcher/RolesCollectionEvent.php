<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use MakinaCorpus\Ucms\Site\Site;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event class for the collection of the sites relative roles.
 */
class RolesCollectionEvent extends Event
{
    const EVENT_NAME = 'roles:collection';

    private $roles;
    private $context;

    /**
     * Default constructor
     *
     * @param string[] $defaultRoles
     *   Keys are identifiers, values are labels
     */
    public function __construct(array $defaultRoles = [], Site $context = null)
    {
        $this->roles = $defaultRoles;
        $this->context = $context;
    }

    /**
     * Getter for roles property
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * Add a relative role
     */
    public function addRole(int $id, string $label): self
    {
        if (isset($this->roles[$id])) {
            throw new \LogicException(sprintf("This relative role identifier is already used (%d).", $id));
        }

        $this->roles[$id] = $label;

        return $this;
    }

    /**
     * Does the event have a context
     */
    public function hasContext(): bool
    {
        return isset($this->context);
    }

    /**
     * Get the event's context
     *
     * @return Site|null
     */
    public function getContext()
    {
        return $this->context;
    }
}
