<?php

namespace MakinaCorpus\Ucms\Group;

class GroupManager
{
    private $database;
    private $storage;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $database
     * @param GroupStorage $storage
     */
    public function __construct(\DatabaseConnection $database, GroupStorage $storage)
    {
        $this->database = $database;
        $this->storage = $storage;
    }

    /**
     * Get storage service
     *
     * @return GroupStorage
     */
    public function getStorage()
    {
        return $this->storage;
    }
}
