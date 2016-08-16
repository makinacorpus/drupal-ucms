<?php

namespace MakinaCorpus\Ucms\Group;

use MakinaCorpus\Ucms\Site\Site;

class GroupManager
{
    private $database;
    private $storage;
    private $access;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $database
     * @param GroupStorage $storage
     * @param GroupAccessService $groupAccess
     */
    public function __construct(\DatabaseConnection $database, GroupStorage $storage, GroupAccessService $access)
    {
        $this->database = $database;
        $this->storage = $storage;
        $this->access = $access;
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

    /**
     * Get access service
     *
     * @return GroupAccessService
     */
    public function getAccess()
    {
        return $this->access;
    }

    /**
     * For given site, depending on its groups, tells default ghost status for nodes
     *
     * @param Site $site
     *
     * @return bool
     */
    public function getSiteDefaultGhostStatus(Site $site)
    {
        throw new \Exception("Not implemented yet");
    }
}
