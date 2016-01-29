<?php

namespace MakinaCorpus\Ucms\Layout;

class Layout
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $siteId;

    /**
     * @var int
     */
    private $nid;

    /**
     * @var []
     */
    private $regions = [];

    /**
     * Default constructor
     */
    public function __construct()
    {
    }

    /**
     * Set identifier
     *
     * @param int $id
     *
     * @return Layout
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

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
     * Set site identifier
     *
     * @param int $id
     *
     * @return Layout
     */
    public function setSiteId($siteId)
    {
        $this->siteId = $siteId;

        return $this;
    }

    /**
     * Get site identifier
     *
     * @return int
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * Set owner account identifier
     *
     * @param int $nodeId
     *
     * @return Layout
     */
    public function setNodeId($nodeId)
    {
        $this->nid = $nodeId;

        return $this;
    }

    /**
     * Get owner account identifier
     *
     * @return int
     */
    public function getNodeId()
    {
        return $this->nid;
    }

    /**
     * Get all registered regions
     *
     * This will return only regions with data
     *
     * @return Region[]
     */
    public function getAllRegions()
    {
        foreach ($this->regions as $name => $region) {
            yield $name => $region;
        }
    }

    /**
     * Get a single region
     *
     * @param string $name
     *
     * @return Region
     */
    public function getRegion($name)
    {
        if (!isset($this->regions[$name])) {
            $this->regions[$name] = new Region($name);
        }

        return $this->regions[$name];
    }
}
