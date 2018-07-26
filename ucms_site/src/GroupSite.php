<?php

namespace MakinaCorpus\Ucms\Site;

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
    static public function create(int $groupId, int $siteId, Site $site = null)
    {
        $instance = new self();

        $instance->group_id = $groupId;
        $instance->site_id = $siteId;

        return $instance;
    }

    private $group_id;
    private $site_id;
    private $site;

    /**
     * Get site identifier
     */
    public function getSiteId(): int
    {
        return (int)$this->site_id;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroupId(): int
    {
        return (int)$this->group_id;
    }

    /**
     * Set preloaded site
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
     */
    public function getSite(): Site
    {
        return $this->site;
    }
}
