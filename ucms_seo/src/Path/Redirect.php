<?php

namespace MakinaCorpus\Ucms\Seo\Path;

/**
 * Immutable representation of a database redirect
 */
class Redirect
{
    private $id;
    private $nid;
    private $site_id;
    private $path;
    private $expires;
    private $expiresAt;

    /**
     * Get identifier
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get node identifier
     *
     * @return int
     */
    public function getNodeId()
    {
        return $this->nid;
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
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get node original route
     *
     * @return string
     */
    public function getNodeRoute()
    {
        return 'node/' . $this->nid;
    }

    /**
     * Has an expiry date
     *
     * @return bool
     */
    public function hasExpiryDate()
    {
        return !!$this->expires;
    }

    /**
     * Get expiry date
     *
     * @return null|\DateTimeImmutable
     */
    public function expiresAt()
    {
        if ($this->expires && !$this->expiresAt) {
            $this->expiresAt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $this->expires);
        }

        return $this->expiresAt;
    }
}
