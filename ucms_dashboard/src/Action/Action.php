<?php

namespace MakinaCorpus\Ucms\Dashboard\Action;

use Drupal\Core\Url;

/**
 * Represent a possible action over a certain item, this is just a value
 * object that will be used to build UI links or buttons
 */
interface Action
{
    const GROUP_DEFAULT = '';

    /**
     * Get action unique identifier
     */
    public function getId(): string;

    /**
     * Get action unique identifier cleaned-up and OK for CSS usage
     */
    public function getDrupalId(): string;

    /**
     * Get Drupal Url instance for rendering
     */
    public function getDrupalUrl(): Url;

    /**
     * Get action group
     */
    public function getGroup(): string;

    /**
     * Get action title
     */
    public function getTitle(): string;

    /**
     * Get action description
     */
    public function getDescription(): string;

    /**
     * Get icon
     */
    public function getIcon(): string;

    /**
     * Get action priority (order in list)
     */
    public function getPriority(): int;

    /**
     * Is the action primary
     */
    public function isPrimary(): bool;

    /**
     * Is the action disabled
     */
    public function isDisabled(): bool;

    /**
     * Is the action granted
     */
    public function isGranted(): bool;
}
