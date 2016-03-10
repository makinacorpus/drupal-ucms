<?php

namespace MakinaCorpus\Ucms\Site\SiteStorage;

use MakinaCorpus\Ucms\Site\Site;

interface StorageInterface
{
    /**
     * Find by hostname
     *
     * @param string $hostname
     * @param boolean $setAsContext
     *
     * @return Site
     *   Site instance, or null if not found
     */
    public function findByHostname($hostname);

    /**
     * Find template sites
     *
     * @return Site[] $site
     */
    public function findTemplates();

    /**
     * Load site by identifier
     *
     * @param int $id
     *
     * @return Site
     *
     * @throws \InvalidArgumentException
     */
    public function findOne($id);

    /**
     * Load all sites from the given identifiers
     *
     * @param array $idList
     * @param string $withAccess
     *
     * @return Site[]
     */
    public function loadAll($idList = [], $withAccess = true);

    /**
     * Save given site
     *
     * If the given site has no identifier, its identifier will be set
     *
     * @param Site $site
     * @param array $fields
     *   If set, update only the given fields
     * @param int $userId
     *   Who did this!
     */
    public function save(Site $site, array $fields = null, $userId = null);

    /**
     * Delete the given sites
     *
     * @param Site $site
     * @param int $userId
     *   Who did this!
     */
    public function delete(Site $site, $userId = null);

    /**
     * Reset cache if any.
     */
    public function resetCache();
}
