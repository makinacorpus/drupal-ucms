<?php

namespace MakinaCorpus\Ucms\Seo\Path;

use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Front-end implementation that caches using a key computed from the request path.
 *
 * Quick performance considerations (done on a page with 233 nodes displayed):
 *  - 1. scenario where cache is down and all are outdated (worst scenario);
 *  - 2. scenario where cache is down and none are outdated (frequent scenario);
 *  - 3. scenario where cache is up and all are outdated (wrong scenario);
 *  - 4. scenario where cache is up and none are outdated (best scenario).
 *
 * After profiling, cachegrind trace sizes:
 * >  ls -alrSh cachegrind.out.*
 * -rw-r--r-- 1 www-data www-data 28M févr. 22 19:50 cachegrind.out.cache_none_outdated (4)
 * -rw-r--r-- 1 www-data www-data 28M févr. 22 19:49 cachegrind.out.no_cache_none_outdated (2)
 * -rw-r--r-- 1 www-data www-data 34M févr. 22 19:48 cachegrind.out.no_cache_all_outdated (1)
 * -rw-r--r-- 1 www-data www-data 34M févr. 22 19:50 cachegrind.out.cache_all_outdated (3)
 *
 * This is not relevant because the site is kinda broken itself.
 *
 * SQL query count (remember that site is kinda broken itself, cache backend
 * is plugged on Redis, so cache queries do not count here):
 *   1. 1330 / 464 / 1794 (SELECT, INSERT, ALL)
 *   2. 286 / 0 / 286
 *   3. 1790 / 466 / 1790 (got some duplicates I guess)
 *   4. 54 / 0 / 54
 *
 * Figures are still really wrong when all aliases are outdated, also please
 * consider the fact that in real life, you will never display 230 nodes on the
 * same page (here, we have an administrator logged-in with his own cart, hence
 * the very wrong *HUGE* URLs count).
 *
 * Some quick conclustions from this:
 *  - best case scenario is as fast as Drupal core is;
 *  - we have to consider that requests are done per bulk of 5 in transactions
 *    so even if there's a lot, it still terribly fast!
 *  - alias deduplication is terrible for performances;
 *  - we need invalidation to be very refined (no whole site invalidation!);
 *  - we should drop some URLs, for example everything that's in admin toolset;
 *  - we must implement a regular cron that randomly rebuild URLs when outdated;
 *  - umenu trees should be statically cached (we have 230+ loads!);
 *  - umenu trees could be remote cached;
 *  - we could attempt to lookup more than once at the same time (for example
 *    during node_load_multiple, and optimistically build the cache);
 *  - OR we could simply allow outdated entries display, and refresh will be
 *    done at redirect time and by the cron, sus completly eliminating the
 *    insert queries at runtime problem
 *  - any other suggestion is welcome.
 */
class AliasCacheLookup
{
    /**
     * Do not store more than this number of links in cache to avoid it growing.
     */
    const MAX_CACHE_SIZE = 1000;

    private $aliasManager;
    private $allowInvalid = true;
    private $cache;
    private $cacheKey;
    private $database;
    private $isLoaded = false;
    private $isModified = false;
    private $loaded = [];
    private $queried = [];
    private $queriedCount = 0;

    /**
     * Default constructor
     *
     * @param AliasManager $aliasManager
     * @param \DatabaseConnection $database
     * @param CacheBackendInterface $cache
     * @param bool $allowInvalid
     */
    public function __construct(AliasManager $aliasManager, \DatabaseConnection $database, CacheBackendInterface $cache, $allowInvalid = true)
    {
        $this->aliasManager = $aliasManager;
        $this->database = $database;
        $this->cache = $cache;
        $this->allowInvalid = $allowInvalid;
    }

    /**
     * Write current cache
     *
     * This will be called by a terminate event
     */
    public function write()
    {
        if ($this->isModified && $this->cacheKey) {
            $this->cache->set($this->cacheKey, $this->queried);
            $this->isModified = false;
        }
    }

    /**
     * Fetch alias for node using the manager, and store it into cache
     *
     * @param int $nodeId
     * @param int $siteId
     *
     * @return string
     */
    private function fetchAlias($nodeId, $siteId)
    {
        // We have no source loaded yet, attempt to find it properly and
        // populate the cache, even if there's nothing to load
        $alias = $this->aliasManager->getPathAlias($nodeId, $siteId);

        $this->isModified = true;

        if ($alias) {
            $this->loaded[$nodeId][$siteId] = $alias;
        }

        return $alias;
    }

    /**
     * Lookup for given source
     *
     * @param int $source
     * @param int $siteId
     *
     * @return string
     */
    public function lookup($nodeId, $siteId)
    {
        if (!$this->isLoaded) {
            $this->load();
            $this->isLoaded = true;
        }

        // Build the array we will store at the end of the query if we have
        // anything changed during the request
        if ($this->queriedCount < self::MAX_CACHE_SIZE) {
            $this->queried[$nodeId][$siteId] = true;
        }
        $this->queriedCount++;

        if (!isset($this->loaded[$nodeId][$siteId])) {
            // We already have cached this path in the past, or it was not in
            // original cache source array, or it is non existing anymore or
            // outdated, our cache is broken, we need to lookup and update
            return $this->fetchAlias($nodeId, $siteId) ?? 'node/' . $nodeId;
        }

        // Best case scenario, it exists!
        return $this->loaded[$nodeId][$siteId];
    }

    /**
     * Load cache
     */
    private function load()
    {
        $this->loaded = [];
        $this->queried = [];
        $this->queriedCount = 0;

        // Sorry, this is nothing to load
        if (!$this->cacheKey) {
            return;
        }

        $cached = $this->cache->get($this->cacheKey);

        // Cache might be invalid, just don't let it broke everything, if not
        // attempt a graceful preload of all routes that are not outdated yet
        if ($cached && is_array($cached->data)) {

            // We need both node identifiers and site identifiers
            $nodeIdList = [];
            $siteIdList = [];
            foreach ($cached->data as $nodeId => $data) {
                $nodeIdList[] = $nodeId;
                foreach ($data as $siteId) {
                    if (!isset($siteIdList[$siteId])) {
                        $siteIdList[$siteId] = $siteId;
                    }
                }
            }

            $query = $this
                ->database
                ->select('ucms_seo_route', 'r')
                ->fields('r', ['node_id', 'site_id', 'route'])
                ->condition('r.site_id', $siteIdList)
                ->condition('r.node_id', $nodeIdList)
            ;

            if (!$this->allowInvalid) {
                $query->condition('r.is_outdated', 0);
            }

            $rows = $query->execute();

            foreach ($rows as $row) {
                $this->loaded[$row->node_id][$row->site_id] = $row->route;
            }
        }
    }

    /**
     * Set internals
     *
     * This will be called on site init event
     *
     * @param int|string $siteId
     * @param string $path
     */
    public function setEnvironment($siteId, $path)
    {
        $cacheKey = 'alias#' . $siteId . '#' . $path;

        if ($cacheKey === $this->cacheKey) {
            return;
        }

        $this->cacheKey = $cacheKey;
        $this->isLoaded = false;
    }
}

