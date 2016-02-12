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
    private $role;

    /**
     * Get user identifier
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->uid;
    }

    /**
     * Get site identifier
     *
     * @return int
     */
    public function getSiteId()
    {
        return $this->site_id;
    }

    /**
     * Get role
     *
     * @return int
     *   One of the Access:ROLE_* constant
     */
    public function getRole()
    {
        return $this->role;
    }
}
