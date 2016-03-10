<?php

namespace MakinaCorpus\Ucms\Site\AccessStorage;

use Drupal\Core\Session\AccountInterface;

use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteAccessRecord;

interface StorageInterface
{
    /**
     * Get user role in site
     *
     * @param AccountInterface $account
     *
     * @return SiteAccessRecord|SiteAccessRecord[]
     */
    public function getUserAccessRecords(AccountInterface $account);

    /**
     * Merge users with role
     *
     * @param Site $site
     * @param int|int[] $userIdList
     * @param int $role
     *   Access::ROLE_* constant
     */
    public function mergeUsersWithRole(Site $site, $userIdList, $role);

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
    public function removeUsersWithRole(Site $site, $userIdList, $role = null);

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
    public function listUsersWithRole(Site $site, $role = null, $limit = 100, $offset = 0);

    /**
     * Count users having a specific role.
     * If role is null, count all users.
     *
     * @param Site $site
     * @param int $role
     *
     * @return int
     */
    public function countUsersWithRole(Site $site, $role = null);

    /**
     * Reset cache if any.
     */
    public function resetCache();
}
