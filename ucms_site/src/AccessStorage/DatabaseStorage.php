<?php

namespace MakinaCorpus\Ucms\Site\AccessStorage;

use Drupal\Core\Session\AccountInterface;

use MakinaCorpus\Ucms\Site\Site;

class DatabaseStorage implements StorageInterface
{
    /**
     * @var \DatabaseConnection
     */
    private $db;

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
     * {@inheritdoc}
     */
    public function getUserAccessRecords(AccountInterface $account)
    {
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
                [':userId' => $account->id()]
            )
        ;

        $r->setFetchMode(\PDO::FETCH_CLASS, 'MakinaCorpus\\Ucms\\Site\\SiteAccessRecord');

        return $r->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public function mergeUsersWithRole(Site $site, $userIdList, $role)
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
     * {@inheritdoc}
     */
    public function removeUsersWithRole(Site $site, $userIdList, $role = null)
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function resetCache()
    {
    }
}
