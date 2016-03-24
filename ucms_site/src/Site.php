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

    /**
     * @var string
     */
    private $attributes;

    public function getId()
    {
        return $this->id;
    }

    public function getTheme()
    {
        return $this->theme;
    }

    public function getHomeNodeId()
    {
        return $this->home_nid;
    }

    public function getAttributes()
    {
        // When loading objects from PDO with 'class' fetch mode, the
        // constructor won't be called, hence this lazy init
        if (!is_array($this->attributes)) {
            if (null === $this->attributes) {
                $this->attributes = [];
            } if (is_string($this->attributes)) {
                $this->attributes = unserialize($this->attributes);
            }
        }

        return $this->attributes;
    }

    public function getAttribute($name, $default = null)
    {
        if ($this->hasAttribute($name)) {
            return $this->attributes[$name];
        }
        return $default;
    }

    public function hasAttribute($name)
    {
        $this->getAttributes();

        return array_key_exists($name, $this->attributes);
    }

    public function setAttribute($name, $value)
    {
        if (null === $value) {
            $this->deleteAttribute($name);
        }

        $this->getAttributes();

        $this->attributes[$name] = $value;
    }

    public function deleteAttribute($name)
    {
        if ($this->hasAttribute($name)) {
            unset($this->attributes[$name]);
        }
    }
}
