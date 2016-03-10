<?php

namespace MakinaCorpus\Ucms\Site;

/**
 * Single access record for a site and user couple
 *
 * This object is immutable
 *
 * @todo
 *   - reference the site object within
 */
class SiteAccessRecord
{
    /**
     * @var int
     */
    private $uid;

    /**
     * @var int
     */
    private $site_id;

    /**
     * @var int
     */
    private $site_state;

    /**
     * @var int
     */
    private $role;

    /**
     * Default constructor
     *
     * @param int $uid
     * @param int $siteId
     * @param int $role
     * @param int $siteState
     */
    public function __construct($uid = null, $siteId = null, $role = null, $siteState = null)
    {
        if (null !== $uid) { // null means we are building this via PDO.
            $this->uid = $uid;
            $this->site_id = $siteId;
            $this->role = $role;
            $this->site_state = $siteState;
        }
    }

    /**
     * Get user identifier
     *
     * @return int
     */
    public function getUserId()
    {
        return (int)$this->uid;
    }

    /**
     * Get site identifier
     *
     * @return int
     */
    public function getSiteId()
    {
        return (int)$this->site_id;
    }

    /**
     * Denormalized site state value
     *
     * @return int
     */
    public function getSiteState()
    {
        return null === $this->site_state ? null : (int)$this->site_state;
    }

    /**
     * Get role
     *
     * @return int
     *   One of the Access:ROLE_* constant
     */
    public function getRole()
    {
        return (int)$this->role;
    }
}
