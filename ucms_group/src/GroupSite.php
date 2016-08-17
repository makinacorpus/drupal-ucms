<?php

namespace MakinaCorpus\Ucms\Group;

use MakinaCorpus\Ucms\Site\Site;

/**
 * Single access record for a group and site couple
 *
 * This object is immutable.
 *
 * Theorically, we wouldn't need this to exist, since we already can load site
 * structures, but the idea behind that is that we need to differenciate a
 * site in normal site admin from a site in group admin, because actions will
 * be different; we also will need the group identifier for actions
 */
class GroupSite
{
    /**
     * Use this only when you can't or don't want to go throught database
     *
     * @param int $groupId
     * @param int $userId
     */
    static public function create($groupId, $siteId, Site $site = null)
    {
        $instance = new self();

        $instance->group_id = $groupId;
        $instance->site_id = $siteId;

        return $instance;
    }

    /**
     * @var int
     */
    private $group_id;

    /**
     * @var int
     */
    private $site_id;

    /**
     * @var Site
     */
    private $site;

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
     * Get group identifier
     *
     * @return int
     */
    public function getGroupId()
    {
        return (int)$this->group_id;
    }

    /**
     * Set preloaded site
     *
     * @param Site $site
     */
    public function setSite(Site $site)
    {
        if ($this->site || $site->getId() !== $this->getSiteId()) {
            throw new \LogicException("this object is not really immutable, but it should be, site is already set anyway");
        }

        $this->site = $site;
    }

    /**
     * Get site
     *
     * @return Site
     */
    public function getSite()
    {
        return $this->site;
    }
}
