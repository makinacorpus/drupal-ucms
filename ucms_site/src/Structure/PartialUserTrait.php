<?php

namespace MakinaCorpus\Ucms\Site\Structure;

/**
 * Implementation of PartialUserInterface suitable for objects loaded via
 * PDO. This trait is immutable.
 */
trait PartialUserTrait /* implements PartialUserInterface */
{
    private $name;
    private $mail;
    private $status;

    /**
     * {@inheritdoc}
     */
    public function getAccountName(): string
    {
        return $this->name ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayName(): string
    {
        return $this->name ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getEmail(): string
    {
        return $this->mail ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function isActive(): bool
    {
        return (bool)$this->status;
    }

    /**
     * {@inheritdoc}
     */
    public function isBlocked(): bool
    {
        return !$this->status;
    }
}
