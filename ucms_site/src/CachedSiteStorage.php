<?php

namespace MakinaCorpus\Ucms\Site;

class CachedSiteStorage extends SiteStorage
{
    /**
     * @var Site[]
     */
    private $hostCache = [];

    /**
     * @var Site[]
     */
    private $idCache = [];

    /**
     * @param Site $site
     *
     * @return Site
     */
    private function add($site)
    {
        if ($site) {
            $this->hostCache[$site->http_host] = $site;
            $this->idCache[$site->getId()] = $site;
        }

        return $site;
    }

    /**
     * @param Site[] $sites
     *
     * @return Site[]
     */
    private function addAll($sites)
    {
        foreach ($sites as $site) {
            $this->add($site);
        }

        return $sites;
    }

    /**
     * {@inheritdoc}
     */
    public function findByHostname($hostname)
    {
        if (isset($this->hostCache[$hostname])) {
            return $this->hostCache[$hostname];
        }

        return $this->add(parent::findByHostname($hostname));
    }

    /**
     * {@inheritdoc}
     */
    public function findOne($id)
    {
        if (isset($this->idCache[$id])) {
            return $this->idCache[$id];
        }

        return $this->add(parent::findOne($id));
    }

    /**
     * {@inheritdoc}
     */
    protected function loadWithConditions($conditions = [], $orderField = null, $order = null, $limit = 100, $withAccess = true, array $additionalTags = [])
    {
        return $this->addAll(parent::loadWithConditions($conditions, $orderField, $order, $limit, $withAccess, $additionalTags));
    }

    /**
     * {@inheritdoc}
     */
    public function loadAll($idList = [], $withAccess = true)
    {
        if (empty($idList)) {
            return [];
        }

        $ret      = [];
        $missing  = [];

        foreach ($idList as $id) {
            if (isset($this->idCache[$id])) {
                $ret[$id] = $this->idCache[$id];
            } else {
                // This will keep ordering when adding missing entries
                $ret[$id] = null;
                $missing[] = $id;
            }
        }

        if (empty($missing)) {
            return $ret;
        }

        foreach (parent::loadAll($missing, $withAccess) as $id => $site) {
            $ret[$id] = $this->add($site);
        }

        return array_filter($ret);
    }

    /**
     * {@inheritdoc}
     */
    public function save(Site $site, array $fields = null, $userId = null)
    {
        $ret = parent::save($site, $fields, $userId);

        $this->add($site);

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Site $site, $userId = null)
    {
        unset(
            $this->hostCache[$site->http_host],
            $this->idCache[$site->id]
        );

        return parent::delete($site);
    }
}
