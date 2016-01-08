<?php

namespace MakinaCorpus\Ucms\Site;

/**
 * Handles site access
 */
class SiteAccessService
{
    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * User access cache
     *
     * @var boolean[][]
     */
    private $accessCache = [];

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     */
    public function __construct(\DatabaseConnection $db)
    {
        $this->db = $db;
    }

    /**
     * Tells if user is webmaster of the current site
     *
     * @param int $siteId
     * @param int $userId
     *
     * @return boolean
     */
    public function userIsWebmaster($siteId, $userId)
    {
        if (isset($this->accessCache[$siteId][$userId])) {
            return $this->accessCache[$siteId][$userId];
        }

        return $this->accessCache[$siteId][$userId] = (bool)$this
            ->db
            ->query(
                "SELECT 1 FROM {ucms_site_access} WHERE site_id = :siteId AND uid = :userId LIMIT 1 OFFSET 0",
                [
                    ':siteId' => $siteId,
                    ':userId' => $userId,
                ]
            )
            ->fetchField()
        ;
    }

    /**
     * Reset internal cache
     *
     * If I did it right, you should never have to use this
     */
    public function resetCache()
    {
        $this->accessCache = [];
    }
}
