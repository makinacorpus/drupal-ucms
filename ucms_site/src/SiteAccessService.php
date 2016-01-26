<?php

namespace MakinaCorpus\Ucms\Site;

use Drupal\Core\Entity\EntityManager;

/**
 * Handles site access
 */
class SiteAccessService
{
    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * @var \DrupalEntityControllerInterface
     */
    private $userController;

    /**
     * User access cache
     *
     * @var boolean[][]
     */
    private $accessCache = [];

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     */
    public function __construct(\DatabaseConnection $db, EntityManager $entityManager)
    {
        $this->db = $db;
        $this->userController = $entityManager->getStorage('user');
    }

    /**
     * Get current user identifier
     *
     * @return int
     */
    protected function getCurrentUserId()
    {
        // FIXME: Inject it instead
        return $GLOBALS['user']->uid;
    }

    /**
     * Does the given user has the given permission
     *
     * This is a proxy to Drupal native user_access() method
     *
     * @param string $permission
     *   One of the Access::PERM_* constant
     * @param int $userId
     *   User account identifier, if none given use the current user from context
     *
     * @return boolean
     */
    public function userHasPermission($permission, $userId = null)
    {
        if (null === $userId) {
            $userId = $this->getCurrentUserId();
        }

        $users = $this->userController->load([$userId]);

        if (!$users) {
            return false;
        }

        return user_access($permission, reset($users));
    }

    /**
     * Is the given user webmaster of the given site
     *
     * @param Site $site
     * @param int $userId
     *
     * @return boolean
     */
    public function userIsWebmaster(Site $site, $userId = null)
    {
        if (null === $userId) {
            $userId = $this->getCurrentUserId();
        }

        $siteId = $site->id;

        if (isset($this->accessCache[$siteId][$userId])) {
            return $this->accessCache[$siteId][$userId];
        }

        return $this->accessCache[$siteId][$userId] = (bool)$this
            ->db
            ->query(
                "SELECT 1 FROM {ucms_site_access} WHERE site_id = :siteId AND uid = :userId LIMIT 1 OFFSET 0",
                [
                    ':siteId' => $siteId,
                    ':userId' => $userId,
                ]
            )
            ->fetchField()
        ;
    }

    /**
     * Can the given user view the given site
     *
     * @param Site $site
     * @param int $userId
     *
     * @return boolean
     */
    public function userCanView(Site $site, $userId = null)
    {
        if (null === $userId) {
            $userId = $this->getCurrentUserId();
        }

        if (SiteState::ON == $site->state) {
            return true;
        }

        switch ($site->state) {

            case SiteState::INIT:
            case SiteState::OFF:
            case SiteState::ARCHIVE:
                return $this->userHasPermission(Access::PERM_SITE_MANAGE_ALL, $userId)
                    || $this->userHasPermission(Access::PERM_SITE_VIEW_ALL, $userId)
                    || $this->userIsWebmaster($site, $userId)
                ;
        }

        return false;
    }

    /**
     * Can the given user manage the given site
     *
     * @param Site $site
     * @param int $userId
     *
     * @return boolean
     */
    public function userCanManage(Site $site, $userId = null)
    {
        if (null === $userId) {
            $userId = $this->getCurrentUserId();
        }

        if ($this->userHasPermission(Access::PERM_SITE_MANAGE_ALL, $userId)) {
            return true;
        }

        switch ($site->state) {

            case SiteState::INIT:
            case SiteState::OFF:
            case SiteState::ON:
                return $this->userIsWebmaster($site, $userId);
        }

        return false;
    }

    /**
     * Can the given user manage the given site webmasters
     *
     * @param Site $site
     * @param int $userId
     *
     * @return boolean
     */
    public function userCanManageWebmasters(Site $site, $userId = null)
    {
        if (null === $userId) {
            $userId = $this->getCurrentUserId();
        }

        return $this->userHasPermission(Access::PERM_SITE_MANAGE_ALL, $userId);
    }

    /**
     * Can the given user delete the given site
     *
     * @param Site $site
     * @param int $userId
     *
     * @return boolean
     */
    public function userCanDelete(Site $site, $userId = null)
    {
        if (null === $userId) {
            $userId = $this->getCurrentUserId();
        }

        return SiteState::ARCHIVE == $site->state && $this->userHasPermission(Access::PERM_SITE_MANAGE_ALL, $userId);
    }

    /**
     * Reset internal cache
     *
     * If I did it right, you should never have to use this
     */
    public function resetCache()
    {
        $this->accessCache = [];
    }
}
