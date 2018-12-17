<?php

namespace MakinaCorpus\Ucms\Seo\Path;

/**
 * Immutable representation of a database redirect
 */
class Redirect
{
    private $expires;
    private $expiresAt;
    private $id;
    private $nid;
    private $node_exists;
    private $node_title;
    private $node_type;
    private $path;
    private $site_id;
    private $site_title;

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

    /**
     * Get site admin title if selected by the query
     *
     * @return null|string
     */
    public function getSiteAdminTitle()
    {
        return $this->site_title;
    }

    /**
     * Get node title if selected by the query
     *
     * @return null|string
     */
    public function getNodeTitle()
    {
        return $this->node_title;
    }

    /**
     * Does not exists
     *
     * WARNING: This will return false if the SELECT query did not JOIN
     * properly on the {node} table.
     */
    public function nodeExists(): bool
    {
        return (bool)$this->node_exists;
    }

    /**
     * Get the node type
     *
     * WARNING: This will return null if the SELECT query did not JOIN
     * properly on the {node} table.
     */
    public function getNodeType()
    {
        return $this->node_type;
    }
}
