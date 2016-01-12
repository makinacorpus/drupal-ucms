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
    public $theme;

    /**
     * @var string
     */
    public $http_host;

    /**
     * @var string
     */
    public $relacement_of;

    /**
     * @var int
     */
    public $uid;
}
