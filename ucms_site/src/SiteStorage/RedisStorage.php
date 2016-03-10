<?php

namespace MakinaCorpus\Ucms\Site\SiteStorage;

use MakinaCorpus\RedisBundle\AbstractRedisAware;
use MakinaCorpus\Ucms\Site\Site;

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

    private function getSiteKey($siteId)
    {
        return $this->getKey(['site', $siteId]);
    }

    /**
     * {@inheritdoc}
     */
    public function findByHostname($hostname)
    {
        // @todo Needs caching here
        return $this->master->findByHostname($hostname);
    }

    /**
     * {@inheritdoc}
     */
    public function findTemplates()
    {
        // @todo Caching here
        return $this->master->findTemplates();
    }

    /**
     * {@inheritdoc}
     */
    public function findOne($id)
    {
        $client = $this->getClient();
        $key = $this->getSiteKey($id);

        $data = $client->hGetAll($key);

        if ($data) {
            // Expand object
            $site = new Site();
            foreach ($data as $key => $value) {
                // Redis data is supposed to be typed, hope for the best
                if ('' === $value) {
                    continue;
                }
                // @todo fix date
                if (is_numeric($value)) {
                    $site->{$key} = (int)$value;
                } else {
                    $site->{$key} = $value;
                }
            }
        } else {
            $site = $this->master->findOne($id);
            // Should work because all properties are public
            // @todo fix date
            $client->hMset($key, (array)$site);
        }

        return $site;
    }

    /**
     * {@inheritdoc}
     */
    public function loadAll($idList = [], $withAccess = true)
    {
        if (!$idList) {
            return [];
        }
        if ($withAccess) {
            return $this->master->loadAll();
        }
        if (1 === count($idList)) {
            return [$this->findOne(reset($idList))];
        }

        $client = $this->getClient();
        $pipe = $client->multi(\Redis::PIPELINE);
        $keys = [];

        foreach ($idList as $id) {
            $keys[$id] = $key = $this->getSiteKey($id);
            $pipe = $pipe->hGetAll($key);
        }

        $ret = $pipe->exec();
        // @todo expand entries

        // @todo load missing

        return $this->master->loadAll($idList, $withAccess);
    }

    /**
     * {@inheritdoc}
     */
    public function save(Site $site, array $fields = null, $userId = null)
    {
        $this->getClient()->del($this->getSiteKey($site->getId()));

        return $this->master->save($site, $fields, $userId);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Site $site, $userId = null)
    {
        $this->getClient()->del($this->getSiteKey($site->getId()));

        return $this->master->delete($site, $userId);
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
