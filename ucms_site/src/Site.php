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
    public $state;

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

    /**
     * Has the given role access
     *
     * @param string $role
     *   One of the Access::ROLE_ constants
     */
    public function roleHasAccess($role)
    {
        switch ($role) {

            case Access::ROLE_ADMIN_FUNC:
            case Access::ROLE_ADMIN_TECH:
            case Access::ROLE_MODERATOR:
            case Access::ROLE_WEBMASTER_LOCAL:
                return in_array($this->state, [
                    State::ARCHIVE,
                    State::INIT,
                    State::OFF,
                    State::ON,
                ]);

            default:
              return $this->state === State::ON;
        }
    }
}
