<?php

namespace Ucms\Layout;

class Item
{
    /**
     * @var int
     */
    private $nid;

    /**
     * @var string
     */
    private $viewMode;

    /**
     * Default constructor
     *
     * @param int $nid
     * @param string $viewMode
     */
    public function __construct($nid, $viewMode = 'teaser')
    {
        $this->nid = $nid;
        $this->viewMode = $viewMode;
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
     * Set view mode
     *
     * @param string $viewMode
     *
     * @return \Ucms\Layout\Item
     */
    public function setViewMode($viewMode)
    {
        $this->viewMode = $viewMode;

        return $this;
    }

    /**
     * Get view mode
     *
     * @return string
     */
    public function getViewMode()
    {
        return $this->viewMode;
    }
}
