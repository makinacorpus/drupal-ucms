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
     * @var string[]
     */
    private $roleListCache;

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
     * Get state transition matrix
     *
     * @todo
     *   This sadly fundamentally broken since role are identifiers, it should
     *   use permissions instead, but this would be severly broken too somehow
     *
     * This is a 3 dimensions array:
     *   - first dimension is from state
     *   - second dimension is to state
     *   - third dimension is a list of roles identifiers
     *
     * @see MakinaCorpus\Ucms\Site\Admin\SiteStateTransitionForm
     *
     * @return int[int[int[]]]
     */
    public function getStateTransitionMatrix()
    {
        return variable_get('ucms_site_state_transition_matrix', []);
    }

    /**
     * Update state transition matrix
     *
     * @param int[int[int[]]] $matrix
     *   The full matrix as described in the ::getStateTransitionMatrix()
     *   method
     */
    public function updateStateTransitionMatrix(array $matrix)
    {
        // Do some cleanup, we don't need to store too many things
        foreach ($matrix as $from => $toList) {
            foreach ($toList as $to => $roleList) {
                $current = array_filter($roleList);
                if ($from == $to || empty($current)) {
                    unset($matrix[$from][$to]);
                } else {
                    // Store the role list as key-value for easier usage
                    $matrix[$from][$to] = array_combine($current, $current);
                }
            }
        }
        variable_set('ucms_site_state_transition_matrix', $matrix);
    }

    /**
     * Get relative roles identifiers
     *
     * @todo
     *   This sadly fundamentally broken since role are identifiers, it should
     *   use permissions instead, but this would be severly broken too somehow
     *
     * @return int[]
     *   Keys are role identifiers, values are Access::ROLE_* constants
     */
    public function getRelativeRoles()
    {
        return variable_get('ucms_site_relative_roles');
    }

    /**
     * Set relative role identifiers
     *
     * @param int[] $roleIdList
     */
    public function updateRelativeRoles($roleIdList)
    {
        variable_set('ucms_site_relative_roles', array_filter(array_map('intval', $roleIdList)));
    }

    /**
     * Get Drupal role list
     *
     * @return string(]
     *   Keys are internal role identifiers, values are role names
     */
    public function getDrupalRoleList()
    {
        if (null === $this->roleListCache) {
            $this->roleListCache = $this->db->query("SELECT rid, name FROM {role} ORDER BY rid")->fetchAllKeyed();
        }

        return $this->roleListCache;
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
     * Get user role identifiers
     *
     * @param int $userId
     *
     * @return int[]
     */
    protected function getUserRoleList($userId = null)
    {
        if (null === $userId) {
            $userId = $this->getCurrentUserId();
        }

        $users = $this->userController->load([$userId]);

        if (!$users) {
            return [];
        }

        return array_keys(reset($users)->roles);
    }

    /**
     * Get user role in site
     *
     * @param Site $site
     * @param int $userId
     *
     * @return int
     *   One of the Access:ROLE_* constants
     */
    protected function getUserRoleCacheValue(Site $site, $userId)
    {
        $siteId = $site->id;

        if (isset($this->accessCache[$siteId][$userId])) {
            return $this->accessCache[$siteId][$userId];
        }

        return $this->accessCache[$siteId][$userId] = (int)$this
            ->db
            ->query(
                "SELECT role FROM {ucms_site_access} WHERE site_id = :siteId AND uid = :userId LIMIT 1 OFFSET 0",
                [
                    ':siteId' => $siteId,
                    ':userId' => $userId,
                ]
            )
            ->fetchField()
        ;
    }

    /**
     * Get user relative role list to site, including global roles
     *
     * @param int $userId
     *
     * @return int[]
     */
    public function getUserSiteRoleList(Site $site, $userId = null)
    {
        $ret = [];

        $relativeRoles  = $this->getRelativeRoles();
        $userSiteRole   = $this->getUserRoleCacheValue($site, $userId);

        // First check the user site roles if any
        if ($userSiteRole) {
            foreach ($relativeRoles as $rid => $role) {
                if ($userSiteRole === $role) {
                    $ret[] = $rid;
                }
            }
        }

        foreach ($this->getUserRoleList($userId) as $rid) {
            // Exlude relative role, they are not global but relative, the fact
            // we set the role onto the Drupal user only means that it has this
            // role only once
            // @todo
            //   consider removing those at the global level for once
            if (!isset($relativeRoles[$rid])) {
                $ret[] = $rid;
            }
        }

        return $ret;
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

        return Access::ROLE_WEBMASTER === $this->getUserRoleCacheValue($site, $userId);
    }

    /**
     * Is the given user contributor of the given site
     *
     * @param Site $site
     * @param int $userId
     *
     * @return boolean
     */
    public function userIsContributor(Site $site, $userId = null)
    {
        if (null === $userId) {
            $userId = $this->getCurrentUserId();
        }

        return Access::ROLE_CONTRIB === $this->getUserRoleCacheValue($site, $userId);
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

        // @todo
        //   this should be based upon a matrix
        switch ($site->state) {

            case SiteState::INIT:
            case SiteState::ARCHIVE:
                return $this->userHasPermission(Access::PERM_SITE_MANAGE_ALL, $userId)
                    || $this->userHasPermission(Access::PERM_SITE_VIEW_ALL, $userId)
                    || $this->userIsWebmaster($site, $userId)
                ;

            case SiteState::OFF:
                return $this->userHasPermission(Access::PERM_SITE_MANAGE_ALL, $userId)
                    || $this->userHasPermission(Access::PERM_SITE_VIEW_ALL, $userId)
                    || $this->userIsWebmaster($site, $userId)
                    || $this->userIsContributor($site, $userId)
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
     * Get allow transition list for the given site and user
     *
     * @param Site $site
     * @param int $userId
     *
     * @return string[]
     *   Keys are state identifiers and values are states names
     */
    public function getAllowedTransitions(Site $site, $userId = null)
    {
        if (null === $userId) {
            $userId = $this->getCurrentUserId();
        }

        $ret = [];
        $states = SiteState::getList();
        $matrix = $this->getStateTransitionMatrix();
        $roles  = $this->getUserSiteRoleList($site, $userId);

        foreach ($states as $state => $name) {
            foreach ($roles as $rid) {
                if (isset($matrix[$site->state][$state][$rid])) {
                    $ret[$state] = $name;
                }
            }
        }

        return $ret;
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
