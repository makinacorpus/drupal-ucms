<?php

namespace MakinaCorpus\Ucms\Site\AccessStorage;

use Drupal\Core\Session\AccountInterface;

use MakinaCorpus\RedisBundle\AbstractRedisAware;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteAccessRecord;

class RedisStorage extends AbstractRedisAware implements StorageInterface
{
    /**
     * Default lifetime
     */
    const DEFAULT_LIFETIME = 604800; // One week

    /**
     * @var StorageInterface
     */
    private $master;

    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * Default constructor
     *
     * @param mixed $redisClient
     * @param StorageInterface $master
     */
    public function __construct($redisClient, StorageInterface $master)
    {
        parent::__construct($redisClient, 'site_access', 'ucms', true);

        $this->master = $master;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserAccessRecords(AccountInterface $account)
    {
        $ret = [];

        /* @var $client \Redis */
        $client = $this->getClient();
        $key = $this->getKey(['grants', $account->id()]);

        $data = $client->hGetAll($key);

        if (empty($data)) {

            $ret = $this->master->getUserAccessRecords($account);
            $data = [];

            foreach ($ret as $grant) {
                $data[$grant->getSiteId()] = $grant->getRole();
            }

            // Set an empty key so that we can effectively test with empty()
            // below (an non existing hash will return [] anyway).
            $data['updated'] = (new \DateTime())->format(\DateTime::ISO8601);

            $client
                ->multi()
                ->hMset($key, $data)
                ->expire($key, self::DEFAULT_LIFETIME)
                ->exec()
            ;

        } else {
            unset($data['updated']);
            foreach ($data as $siteId => $role) {
                $ret[] = new SiteAccessRecord($account->id(), $siteId, $role);
            }
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    private function updateUsers($siteId, $userIdList, $role)
    {
        // @todo a better algorithm would be to update or write if key does not
        // exists, but right now this is enough
        return $this->removeUsers($siteId, $userIdList);
    }

    /**
     * {@inheritdoc}
     */
    private function removeUsers($siteId, $userIdList)
    {
        if (empty($userIdList)) {
            return;
        }

        if (!is_array($userIdList) && !$userIdList instanceof \Traversable) {
            $userIdList = [$userIdList];
        }

        /* @var $client \Redis */
        $client = $this->getClient();

        $keys = [];

        foreach ($userIdList as $userId) {
            $keys = $this->getKey(['grants', $userId]);
        }

        // @todo a better algorithm would be to update or write if key does not
        // exists, but right now this is enough
        $client->del($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function mergeUsersWithRole(Site $site, $userIdList, $role)
    {
        $this->updateUsers($site->getId(), $userIdList, $role);

        return $this->master->mergeUsersWithRole($site, $userIdList, $role);
    }

    /**
     * {@inheritdoc}
     */
    public function removeUsersWithRole(Site $site, $userIdList, $role = null)
    {
        $this->removeUsers($site->getId(), $userIdList);

        // This is only being used in admin, do not cache
        return $this->master->removeUsersWithRole($site, $userIdList, $role);
    }

    /**
     * {@inheritdoc}
     */
    public function listUsersWithRole(Site $site, $role = null, $limit = 100, $offset = 0)
    {
        // This is only being used in admin, do not cache
        return $this->master->listUsersWithRole($site, $role, $limit, $offset);
    }

    /**
     * {@inheritdoc}
     */
    public function countUsersWithRole(Site $site, $role = null)
    {
        // This is only being used in admin, do not cache
        return $this->master->countUsersWithRole($site, $role);
    }

    /**
     * {@inheritdoc}
     */
    public function resetCache()
    {
        // @todo Clear stored data
        return $this->master->resetCache();
    }
}
