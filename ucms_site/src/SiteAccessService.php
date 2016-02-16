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
     * @var EntityManager
     */
    private $entityManager;

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
     * @param EntityManager $entityManager
     */
    public function __construct(\DatabaseConnection $db, EntityManager $entityManager)
    {
        $this->db = $db;
        $this->entityManager = $entityManager;
    }

    /**
     * Get user controller
     *
     * This needs to be done this way because the entity subsystem might not
     * be initialized yet when this object is
     *
     * @return \DrupalEntityControllerInterface
     */
    private function getUserController()
    {
        return $this->entityManager->getStorage('user');
    }

    /**
     * Get current user identifier
     *
     * @return int
     */
    private function getCurrentUserId()
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
    private function getUserRoleList($userId = null)
    {
        if (null === $userId) {
            $userId = $this->getCurrentUserId();
        }

        $users = $this->getUserController()->load([$userId]);

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
    private function getUserRoleCacheValue(Site $site, $userId)
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
     * Get user relative role list to site, including global roles
     *
     * @param int $userId
     *
     * @return int[]
     */
    public function getRelativeUserRoleList(Site $site, $userId = null)
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

        $users = $this->getUserController()->load([$userId]);

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
     * Can the user reference this node on one of his sites
     *
     * @param stdClass $node
     * @param int $userId
     *
     * @return boolean
     */
    public function userCanReference($node, $userId)
    {
        // Let's say, from this very moment, that as long as the user can see
        // the node he might want to add it on one of his sites
        return true;
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
     * Can the given user see administrative information about the site
     *
     * @param Site $site
     * @param int $userId
     */
    public function userCanOverview(Site $site, $userId = null)
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
                return $this->userIsContributor($site, $userId)
                    || $this->userIsWebmaster($site, $userId);

            default:
                return $this->userIsWebmaster($site, $userId);
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
     * Can the given user switch the given site to the given state
     *
     * @param Site $iste
     * @param int $state
     * @param int $userId
     *
     * @return boolean
     */
    public function userCanSwitch($site, $state, $userId = null)
    {
        if (null === $userId) {
            $userId = $this->getCurrentUserId();
        }

        $allowed = $this->getAllowedTransitions($site, $userId);

        return isset($allowed[$state]);
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
        $roles  = $this->getRelativeUserRoleList($site, $userId);

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
     * Merge users with role
     *
     * @param Site $site
     * @param int|int[] $userIdList
     * @param int $role
     *   Access::ROLE_* constant
     */
    private function mergeUsersWithRole(Site $site, $userIdList, $role)
    {
        if (!is_array($userIdList) && !$userIdList instanceof \Traversable) {
            $userIdList = [$userIdList];
        }

        foreach ($userIdList as $userId) {
            // Could be better with a load before and a single bulk insert
            // and a single bulk update, but right now let's go with simple,
            $this
                ->db
                ->merge('ucms_site_access')
                ->key(['site_id' => $site->id, 'uid' => $userId])
                ->fields(['role' => $role])
                ->execute()
            ;
            // Let any exception pass, any exception would mean garbage has
            // been given to this method
        }
    }

    /**
     * Remove users with role
     *
     * If any of the users is webmaster, this role will be kept
     *
     * @param Site $site
     * @param int|int[] $userIdList
     * @param int $role
     *   Access::ROLE_* constant
     */
    private function removeUsersWithRole(Site $site, $userIdList, $role = null)
    {
        $q = $this
            ->db
            ->delete('ucms_site_access')
            ->condition('site_id', $site->id)
            ->condition('uid', $userIdList)
        ;

        if ($role) {
            $q->condition('role', $role);
        }

        $q->execute();
    }

    /**
     * List users with role
     *
     * If role is null, list all users
     *
     * @param Site $site
     * @param int $role
     * @param int $limit
     * @param int $offset
     *
     * @return SiteAccessRecord[]
     */
    private function listUsersWithRole(Site $site, $role = null, $limit = 100, $offset = 0)
    {
        $q = $this
            ->db
            ->select('ucms_site_access', 'u')
            ->fields('u')
            ->condition('u.site_id', $site->id)
        ;

        // @todo
        //  - should we add an added date in the access table?
        //  - return a cursor instead ? with a count() method for paging

        if ($role) {
            $q->condition('u.role', $role);
        }

        /* @var $q \SelectQuery */
        $r = $q
            ->range($offset, $limit)
            ->orderBy('u.uid')
            ->execute()
        ;

        /* @var $r \PDOStatement */
        $r->setFetchMode(\PDO::FETCH_CLASS, 'MakinaCorpus\\Ucms\\Site\\SiteAccessRecord');

        return $r->fetchAll();
    }

    /**
     * Add webmasters
     *
     * If any of the users is contributor, it will be ranked to webmaster
     *
     * @param Site $site
     * @param int|int[] $userIdList
     */
    public function addWebmasters(Site $site, $userIdList)
    {
        $this->mergeUsersWithRole($site, $userIdList, Access::ROLE_WEBMASTER);
    }

    /**
     * Remove webmasters
     *
     * If any of the users is contributor, this role will be kept
     *
     * @param Site $site
     * @param int|int[] $userIdList
     */
    public function removeWebmasters(Site $site, $userIdList)
    {
        $this->removeUsersWithRole($site, $userIdList, Access::ROLE_WEBMASTER);
    }

    /**
     * Add contributors
     *
     * If any of the users is webmaster, it will be lowered to contributor
     *
     * @param Site $site
     * @param int|int[] $userIdList
     */
    public function addContributors(Site $site, $userIdList)
    {
        $this->mergeUsersWithRole($site, $userIdList, Access::ROLE_CONTRIB);
    }

    /**
     * Remove contributors
     *
     * @param Site $site
     * @param int|int[] $userIdList
     */
    public function removeContributors(Site $site, $userIdList)
    {
        $this->removeUsersWithRole($site, $userIdList, Access::ROLE_CONTRIB);
    }

    /**
     * Remove users from site whatever is their role
     *
     * @param Site $site
     * @param int|int[] $userIdList
     */
    public function removeUsers(Site $site, $userIdList)
    {
        $this->removeUsersWithRole($site, $userIdList);
    }

    /**
     * List webmasters
     *
     * @param Site $site
     *
     * @return SiteAccessRecord[]
     */
    public function listWebmasters(Site $site)
    {
        return $this->listUsersWithRole($site, Access::ROLE_WEBMASTER);
    }

    /**
     * List contributors
     *
     * @param Site $site
     *
     * @return SiteAccessRecord[]
     */
    public function listContributors(Site $site)
    {
        return $this->listUsersWithRole($site, Access::ROLE_CONTRIB);
    }

    /**
     * List contributors
     *
     * @param Site $site
     *
     * @return SiteAccessRecord[]
     */
    public function listAllUsers(Site $site)
    {
        return $this->listUsersWithRole($site);
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
