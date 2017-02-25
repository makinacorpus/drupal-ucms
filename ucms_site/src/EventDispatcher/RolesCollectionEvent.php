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

    /**
     * Relative roles.
     *
     * @var string[]
     */
    protected $roles;

    /**
     * Contextual site of the event.
     *
     * @var Site
     */
    protected $context;

    /**
     * Constructor.
     *
     * @param [] $baseRoles
     *  Base roles provided by default.
     * @param Site $context
     *  Site for which we want to collect relative roles.
     */
    public function __construct(array $baseRoles = [], Site $context = null)
    {
        $this->roles = $baseRoles;
        $this->context = $context;
    }

    /**
     * Getter for roles property.
     *
     * @return [] Labels keyed by identifiers.
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Add a relative role.
     *
     * @param integer $rrid
     *  The relative role identifier.
     * @param mixed $label
     *  The relative role label.
     *
     * @throws InvalidArgumentException if the provided role identifier is not an integer.
     * @throws LogicException if the provided role identifier is already used.
     *
     * @return RolesCollectionEvent
     */
    public function addRole($rrid, $label)
    {
        if (!is_integer($rrid)) {
            throw new \InvalidArgumentException("The relative role identifier must be an integer.");
        }
        if (isset($this->roles[$rrid])) {
            throw new \LogicException(sprintf("This relative role identifier is already used (%d).", $rrid));
        }

        $this->roles[$rrid] = $label;
        return $this;
    }

    /**
     * Does the event have a context.
     *
     * @return boolean
     */
    public function hasContext()
    {
        return (boolean) $this->context;
    }

    /**
     * Get the event's context.
     *
     * @return Site|null
     */
    public function getContext()
    {
        return $this->context;
    }
}
