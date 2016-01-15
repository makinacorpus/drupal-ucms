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
    public $relacement_of = '';

    /**
     * @var int
     */
    public $template = null;

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
}
