<?php

namespace MakinaCorpus\Ucms\Site;

use MakinaCorpus\Ucms\Site\Structure\AttributesTrait;
use MakinaCorpus\Ucms\Site\Structure\DatesTrait;

/**
 * Site data structure, carries the access logic
 *
 * Properties are public because of Drupal way of loading objects
 */
class Site
{
    use AttributesTrait;
    use DatesTrait;

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $title_admin;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $type = null;

    /**
     * @var int
     */
    public $state = 0;

    /**
     * @var string
     */
    public $theme = null;

    /**
     * @var string
     */
    public $http_host;

    /**
     * @var string
     */
    public $http_redirects = '';

    /**
     * @var string
     */
    public $replacement_of = '';

    /**
     * @var int (boolean usage)
     */
    public $is_public = 0;

    /**
     * @var int
     */
    public $template_id = null;

    /**
     * @var int (but should be a boolean)
     */
    public $is_template = 0;

    /**
     * @var int
     */
    public $uid = 0;

    /**
     * @var int
     */
    public $home_nid = null;

    /**
     * @var int
     */
    public $group_id;

    public function getId()
    {
        return (int)$this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getAdminTitle()
    {
        return $this->title_admin;
    }

    public function getState()
    {
        return (int)$this->state;
    }

    public function getHostname()
    {
        return $this->http_host;
    }

    public function getTheme()
    {
        return $this->theme;
    }

    public function getOwnerUserId()
    {
        return $this->uid;
    }

    public function setHomeNodeId($nodeId)
    {
        $this->home_nid = $nodeId;
    }

    public function getHomeNodeId()
    {
        return $this->home_nid;
    }

    public function isPublic()
    {
        return (bool)$this->is_public;
    }

    /**
     * Get group identifier
     *
     * If the 'ucms_group' module is not enabled, this will always be null
     *
     * @return int
     */
    public function getGroupId()
    {
        return $this->group_id;
    }
}
