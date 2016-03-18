<?php

namespace MakinaCorpus\Ucms\Site;

use Drupal\Core\Session\AccountInterface;

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
    public function __construct(\DatabaseConnection $db)
    {
        $this->db = $db;
    }

    /**
     * Get user role in site
     *
     * @param AccountInterface $account
     * @param Site $site
     *
     * @return SiteAccessRecord|SiteAccessRecord[]
     */
    private function getUserRoleCacheValue(AccountInterface $account, Site $site = null)
    {
        $userId = $account->id();

        if (!isset($this->accessCache[$userId])) {
            $r = $this
                ->db
                ->query(
                    "
                        SELECT
                            a.uid, a.site_id, a.role, s.state AS site_state
                        FROM {ucms_site_access} a
                        JOIN {ucms_site} s
                            ON s.id = a.site_id
                        WHERE
                            a.uid = :userId
                    ",
                    [':userId' => $userId]
                )
            ;

            $r->setFetchMode(\PDO::FETCH_CLASS, 'MakinaCorpus\\Ucms\\Site\\SiteAccessRecord');

            // Can't use fetchAllAssoc() because properties are private on the
            // objects built by PDO
            $this->accessCache[$userId] = [];
            foreach ($r->fetchAll() as $record) {
                $this->accessCache[$userId][$record->getSiteId()] = $record;
            }
        }

        if (null !== $site) {
            if (!empty($this->accessCache[$userId][$site->id])) {
                return $this->accessCache[$userId][$site->id];
            } else {
                return Access::ROLE_NONE;
            }
        }

        return $this->accessCache[$userId];
    }

    /**
     * Get the user roles for all sites
     *
     * @param AccountInterface $account
     *
     * @return SiteAccessRecord[]
     */
    public function getUserRoles(AccountInterface $account)
    {
        return $this->getUserRoleCacheValue($account);
    }

    /**
     * Get the user role for a given site
     *
     * @param AccountInterface $account
     * @param Site $site
     *
     * @return SiteAccessRecord
     */
    public function getUserRole(AccountInterface $account, Site $site)
    {
        return $this->getUserRoleCacheValue($account, $site);
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
     * Get a Drupal role name
     *
     * @param int $rid
     * @return string
     */
    public function getDrupalRoleName($rid)
    {
        $roles = $this->getDrupalRoleList();
        return $roles[(string) $rid];
    }

    /**
     * Get a Relative role name, i.e. the name of the matching Drupal role
     *
     * @param int $rrid Relative role ID (Access::ROLE_* constant)
     * @return string
     */
    public function getRelativeRoleName($rrid)
    {
        if ($rid = array_keys($this->getRelativeRoles(), $rrid)) {
            return $this->getDrupalRoleName(reset($rid));
        }
    }

    /**
     * Get user relative role list to site, including global roles
     *
     * @param AccountInterface $account
     * @param Site $site
     *
     * @return int[]
     */
    public function getRelativeUserRoleList(AccountInterface $account, Site $site)
    {
        $ret = [];

        $relativeRoles  = $this->getRelativeRoles();
        $grant          = $this->getUserRoleCacheValue($account, $site);

        // First check the user site roles if any
        if ($grant) {
            foreach ($relativeRoles as $rid => $role) {
                if ($grant->getRole() === $role) {
                    $ret[] = $rid;
                }
            }
        }

        foreach ($account->getRoles() as $rid) {
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
     * Is the given user a webmaster.
     * If a site is given, is the given user webmaster of this site.
     *
     * @param AccountInterface $account
     * @param Site $site
     *
     * @return boolean
     */
    public function userIsWebmaster(AccountInterface $account, Site $site = null)
    {
        if (null === $site) {
            foreach ($this->getUserRoleCacheValue($account) as $grant) {
                if (Access::ROLE_WEBMASTER === $grant->getRole()) {
                    return true;
                }
            }
            return false;
        }

        if ($grant = $this->getUserRoleCacheValue($account, $site)) {
            return Access::ROLE_WEBMASTER === $grant->getRole();
        }

        return false;
    }

    /**
     * Is the given user a contributor.
     * If a site is given, the given user contributor of this site.
     *
     * @param AccountInterface $account
     * @param Site $site
     *
     * @return boolean
     */
    public function userIsContributor(AccountInterface $account, Site $site = null)
    {
        if (null === $site) {
            foreach ($this->getUserRoleCacheValue($account) as $grant) {
                if (Access::ROLE_CONTRIB === $grant->getRole()) {
                    return true;
                }
            }
            return false;
        }

        if ($grant = $this->getUserRoleCacheValue($account, $site)) {
            return Access::ROLE_CONTRIB === $grant->getRole();
        }

        return false;
    }


    /**
     * Is the first user allowed to manage the second one.
     *
     * @param AccountInterface $controledUser
     * @param AccountInterface $managedUser
     *
     * @return boolean
     */
    public function userCanManageUser(AccountInterface $controledUser, AccountInterface $managedUser)
    {
        $query = $this->db->select('ucms_site_access', 'sac');
        $query->addExpression('COUNT(*)');
        $query->join('ucms_site_access', 'sam', "sam.site_id = sac.site_id");
        $query->join('ucms_site', 's', "s.id = sac.site_id");
        $query->condition('sac.uid', $controledUser->id());
        $query->condition('sac.role', Access::ROLE_WEBMASTER);
        $query->condition('sam.uid', $managedUser->id());
        $query->condition('s.uid', $managedUser->id(), '<>');

        return (bool) $query->execute()->fetchField();
    }


    /**
     * Can the given user view the given site
     *
     * @param Site $site
     * @param int $userId
     *
     * @return boolean
     */
    public function userCanView(AccountInterface $account, Site $site)
    {
        if (SiteState::ON == $site->state) {
            return true;
        }

        // @todo
        //   this should be based upon a matrix
        switch ($site->state) {

            case SiteState::INIT:
            case SiteState::ARCHIVE:
                return $account->hasPermission(Access::PERM_SITE_MANAGE_ALL)
                    || $account->hasPermission(Access::PERM_SITE_VIEW_ALL)
                    || $this->userIsWebmaster($account, $site)
                ;

            case SiteState::OFF:
                return $account->hasPermission(Access::PERM_SITE_MANAGE_ALL)
                    || $account->hasPermission(Access::PERM_SITE_VIEW_ALL)
                    || $this->userIsWebmaster($account, $site)
                    || $this->userIsContributor($account, $site)
                ;
        }

        return false;
    }

    /**
     * Can the given user see administrative information about the site
     *
     * @param AccountInterface $account
     * @param Site $site
     */
    public function userCanOverview(AccountInterface $account, Site $site)
    {
        if ($account->hasPermission(Access::PERM_SITE_MANAGE_ALL)) {
            return true;
        }

        switch ($site->state) {

            case SiteState::INIT:
            case SiteState::OFF:
            case SiteState::ON:
                return $this->userIsContributor($account, $site)
                    || $this->userIsWebmaster($account, $site);

            default:
                return $this->userIsWebmaster($account, $site);
        }

        return false;
    }

    /**
     * Can the given user manage the given site
     *
     * @param AccountInterface $account
     * @param Site $site
     *
     * @return boolean
     */
    public function userCanManage(AccountInterface $account, Site $site)
    {
        if ($account->hasPermission(Access::PERM_SITE_MANAGE_ALL)) {
            return true;
        }

        switch ($site->state) {

            case SiteState::INIT:
            case SiteState::OFF:
            case SiteState::ON:
                return $this->userIsWebmaster($account, $site);
        }

        return false;
    }

    /**
     * Can the given user manage the given site webmasters
     *
     * @param AccountInterface $account
     * @param Site $site
     *
     * @return boolean
     */
    public function userCanManageWebmasters(AccountInterface $account, Site $site)
    {
        return $account->hasPermission(Access::PERM_SITE_MANAGE_ALL) || $this->userIsWebmaster($account, $site);
    }

    /**
     * Can the given user switch the given site to the given state
     *
     * @param AccountInterface $account
     * @param Site $site
     * @param int $state
     *
     * @return boolean
     */
    public function userCanSwitch(AccountInterface $account, $site, $state)
    {
        $allowed = $this->getAllowedTransitions($account, $site);

        return isset($allowed[$state]);
    }

    /**
     * Can the given user delete the given site
     *
     * @param AccountInterface $account
     * @param Site $site
     *
     * @return boolean
     */
    public function userCanDelete(AccountInterface $account, Site $site)
    {
        return SiteState::ARCHIVE == $site->state && $account->hasPermission(Access::PERM_SITE_MANAGE_ALL);
    }

    /**
     * Get allow transition list for the given site and user
     *
     * @param AccountInterface $account
     * @param Site $site
     *
     * @return string[]
     *   Keys are state identifiers and values are states names
     */
    public function getAllowedTransitions(AccountInterface $account, Site $site)
    {
        $ret = [];
        $states = SiteState::getList();
        $matrix = $this->getStateTransitionMatrix();
        $roles  = $this->getRelativeUserRoleList($account, $site);

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

        $this->resetCache();
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

        $this->resetCache();
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
    public function listUsersWithRole(Site $site, $role = null, $limit = 100, $offset = 0)
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
     * Count users having a specific role.
     * If role is null, count all users.
     *
     * @param Site $site
     * @param int $role
     *
     * @return int
     */
    public function countUsersWithRole(Site $site, $role = null)
    {
        /* @var $q \SelectQuery */
        $q = $this->db
            ->select('ucms_site_access', 'u')
            ->condition('u.site_id', $site->id);

        if ($role) {
            $q->condition('u.role', $role);
        }

        $q->addExpression('COUNT(*)');

        /* @var $r \PDOStatement */
        $r = $q->execute();

        return $r->fetchField();
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

    /**
     * Check if user can edit tree
     *
     * @param \Drupal\Core\Session\AccountInterface $account
     * @param \MakinaCorpus\Ucms\Site\Site $site
     * @return bool
     */
    public function userCanEditTree(AccountInterface $account, Site $site) {
        return $this->userIsWebmaster($account, $site);
    }
}
