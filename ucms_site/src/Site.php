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
    const ALLOWED_PROTOCOL_PASS = 0;
    const ALLOWED_PROTOCOL_HTTP = 1;
    const ALLOWED_PROTOCOL_HTTPS = 2;
    const ALLOWED_PROTOCOL_ALL = 3;

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
     * @var int
     */
    public $allowed_protocols = self::ALLOWED_PROTOCOL_PASS;

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
    public $has_home = 0;

    /**
     * @var int
     */
    public $favicon = null;

    /**
     * @var int
     */
    public $group_id;

    public function getId(): int
    {
        return (int)$this->id;
    }

    public function getTitle(): string
    {
        return $this->title ?? '';
    }

    public function getAdminTitle(): string
    {
        return $this->title_admin ?? $this->title ?? '';
    }

    public function getRawAdminTitle(): string
    {
        return $this->title_admin ?? '';
    }

    public function getType(): string
    {
        return $this->type ?? '';
    }

    public function getState(): int
    {
        return (int)$this->state;
    }

    public function getHostname(): string
    {
        return $this->http_host ?? '';
    }

    public function getTheme(): string
    {
        return $this->theme ?? '';
    }

    public function getTemplateId(): int
    {
        return (int)$this->template_id;
    }

    public function isTemplate(): bool
    {
        return (bool)$this->is_template;
    }

    /**
     * Is the 'https' secure protocol allowed
     */
    public function isHttpsAllowed(): bool
    {
        return self::ALLOWED_PROTOCOL_HTTP != $this->allowed_protocols;
    }

    /**
     * Is the 'http' protocol allowed
     *
     * @return boolean
     */
    public function isHttpAllowed(): bool
    {
        return self::ALLOWED_PROTOCOL_HTTPS != $this->allowed_protocols;
    }

    /**
     * Get the default site scheme
     *
     * @return int
     *   One of the Site::ALLOWED_PROTOCOL_* constants
     */
    public function getAllowedProtocols(): int
    {
        return (int)$this->allowed_protocols;
    }

    public function getOwnerUserId(): int
    {
        return $this->uid ?? 0;
    }

    public function setHomeNodeId(int $nodeId)
    {
        $this->home_nid = $nodeId;
    }

    public function hasHome(): bool
    {
        return (bool)$this->has_home;
    }

    public function getHomeNodeId(): int
    {
        return (int)$this->home_nid;
    }

    public function isPublic(): bool
    {
        return (bool)$this->is_public;
    }

    /**
     * Get group identifier
     */
    public function getGroupId(): int
    {
        return (int)$this->group_id;
    }

    /**
     * Get favicon fid
     */
    public function getFavicon(): int
    {
        return (int)$this->favicon;
    }

    /**
     * Set favicon fid
     */
    public function setFavicon(int $fid)
    {
        $this->favicon = $fid;
    }

    public function getReplacementOf(): string
    {
        return $this->replacement_of ?? '';
    }

    public function getHttpRedirects(): string
    {
        return $this->http_redirects ?? '';
    }
}
