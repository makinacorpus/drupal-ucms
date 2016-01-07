<?php

namespace Ucms\Layout;

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
    private $uid;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $adminTitle;

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
     * @return \Ucms\Layout\Layout
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
     * @return \Ucms\Layout\Layout
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
     * @param int $accountId
     *
     * @return \Ucms\Layout\Layout
     */
    public function setAccountId($accountId)
    {
        $this->uid = $accountId;

        return $this;
    }

    /**
     * Get owner account identifier
     *
     * @return int
     */
    public function getAccountId()
    {
        return $this->uid;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return \Ucms\Layout\Layout
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set administrative title
     *
     * @param string $title
     *
     * @return \Ucms\Layout\Layout
     */
    public function setAdminTitle($title)
    {
        $this->adminTitle = $title;

        return $this;
    }

    /**
     * Get adminitrative title
     *
     * @return string
     */
    public function getAdminTitle()
    {
        return $this->adminTitle;
    }

    /**
     * Get all registered regions
     *
     * This will return only regions with data
     *
     * @return \Ucms\Layout\Region[]
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
     * @return \Ucms\Layout\Region
     */
    public function getRegion($name)
    {
        if (!isset($this->regions[$name])) {
            $this->regions[$name] = new Region($name);
        }

        return $this->regions[$name];
    }
}
