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
    public function getAccountName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayName()
    {
        return check_plain($this->name); // @todo this would need format_username
    }

    /**
     * {@inheritdoc}
     */
    public function getEmail()
    {
        return $this->mail;
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        return (bool)$this->status;
    }

    /**
     * {@inheritdoc}
     */
    public function isBlocked()
    {
        return !$this->status;
    }
}
