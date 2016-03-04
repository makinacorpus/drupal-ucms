<?php

namespace MakinaCorpus\Ucms\Site;

/**
 * Site data structure, carries the access logic
 *
 * Properties are public because of Drupal way of loading objects
 */
class Site
{
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
     * @var \DateTime
     */
    public $ts_created;

    /**
     * @var \DateTime
     */
    public $ts_changed;

    /**
     * @var int
     */
    public $home_nid = null;

    public function getId()
    {
        return $this->id;
    }
}
