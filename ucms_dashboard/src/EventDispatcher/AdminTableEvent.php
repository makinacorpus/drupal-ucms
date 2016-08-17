<?php

namespace MakinaCorpus\Ucms\Dashboard\EventDispatcher;

use MakinaCorpus\Ucms\Dashboard\Table\AdminTable;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * An admin information table is being displayed, append stuff in there
 */
class AdminTableEvent extends GenericEvent
{
    /**
     * Default constructor
     *
     * @param AdminTable $table
     */
    public function __construct(AdminTable $table)
    {
        parent::__construct($table);
    }

    /**
     * Get table
     *
     * @return AdminTable
     */
    public function getTable()
    {
        return $this->getSubject();
    }
}
