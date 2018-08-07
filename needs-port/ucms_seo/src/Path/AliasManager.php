<?php

namespace MakinaCorpus\Ucms\Seo\Path;

use MakinaCorpus\Umenu\TreeProviderInterface;

/**
 * Node alias manager, aliases are always computed based upon menus.
 *
 * Result for speed and scalability purpose will be stored into a custom
 * router table; but may be recomputed at anytime when necessary.
 *
 * @todo protect the whole load+recompute+save algorithm using for update
 *   and retry in a transaction; it'll be much safer
 * @todo implement the redirection on hook_menu_status_alter() in case
 *   of a 404 not/found with a simple select query
 * @todo unit test it
 */
class AliasManager
{
    /**
     * Default expiry interval for backed-up routes into the redirect table
     */
    const DEFAULT_EXPIRE = 'now +6 month';

    /**
     * @var \DatabaseConnection
     */
    private $database;

    /**
     * @var TreeProviderInterface
     */
    private $treeProvider;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $database
     * @param TreeProviderInterface $treeProvider
     */
    public function __construct(\DatabaseConnection $database, TreeProviderInterface $treeProvider)
    {
        $this->database = $database;
        $this->treeProvider = $treeProvider;
    }

    /**
     * Compute alias for node on site
     *
     * @todo this algorithm does up to 3 sql queries, one for finding the
     *   correct menu tree, another for loading it, and the third to lookup
     *   the segments, a better approach would be to merge this code in umenu
     *   itself, buy adding the 'segment' column in the menu, with a unique
     *   constraint on (parent_id, segment).
     *
     * @param int $nodeId
     * @param int $siteId
     *
     * @return null|string
     */
    public function computePathAlias($nodeId, $siteId)
    {
        $nodeIdList = [];
        $menuId = $this->treeProvider->findTreeForNode($nodeId, ['site_id' => $siteId]);

        if ($menuId) {
            // Load the tree, with no access checks, else some parents might
            // be hidden from it and the resulting path would be a failure.
            $tree = $this->treeProvider->buildTree($menuId, false);
            $trail = $tree->getMostRevelantTrailForNode($nodeId);
            if ($trail) {
                foreach ($trail as $item) {
                    $nodeIdList[] = $item->getNodeId();
                }
            } else {
                // This should not happen, but it still can be possible
                $nodeIdList[] = $nodeId;
            }
        } else {
            $nodeIdList[] = $nodeId;
        }

        // And now, query all at once.
        $segments = $this
            ->database
            ->query(
                "SELECT nid, alias_segment FROM {ucms_seo_node} WHERE nid IN (:nodes)",
                [':nodes' => $nodeIdList]
            )
            ->fetchAllKeyed()
        ;

        // This rather unperforming, but it will only act on very small
        // arrays, so I guess that for now, this is enough.
        $pieces = [];
        foreach ($nodeIdList as $nodeId) {
            if (isset($segments[$nodeId])) {
                $pieces[] = $segments[$nodeId];
            }
        }

        // This is probably not supposed to happen, but it might
        if (!$pieces) {
            return null;
        }

        return implode('/', $pieces);
    }

    /**
     * Deduplicate alias if one or more already exsist
     *
     * @param int $nodeId
     * @param int $siteId
     * @param string $alias
     *
     * @return string
     */
    private function deduplicate($nodeId, $siteId, $alias)
    {
        $dupFound   = false;
        $current    = $alias;
        $increment  = 0;

        do {
            $dupFound = (bool)$this
                ->database
                ->query(
                    "SELECT 1 FROM {ucms_seo_route} WHERE route = :route AND site_id = :site",
                    [':route' => $current, ':site' => $siteId]
                )
                ->fetchField()
            ;

            if ($dupFound) {
                $current = $alias . '-' . ($increment++);
            }

        } while ($dupFound);

        return $current;
    }

    /**
     * Store given node alias in given site
     *
     * @param int $nodeId
     * @param int $siteId
     * @param string $outdated
     *   Outdated route if exists
     *
     * @return string
     */
    private function computeAndStorePathAlias($nodeId, $siteId, $outdated = null)
    {
        $transaction = null;

        try {
            $transaction = $this->database->startTransaction();
            $computed = $this->computePathAlias($nodeId, $siteId);

            // In all cases, delete outdated item if exists and backup
            // it into the {ucms_seo_redirect} table with an expire. Not
            // for self, and for all people still using MySQL, RETURNING
            // clause would have been great here.
            $this
                ->database
                ->query(
                    "DELETE FROM {ucms_seo_route} WHERE node_id = :node AND site_id = :site",
                    [':node' => $nodeId, ':site' => $siteId]
                )
            ;

            // @todo ucms_seo_redirect does not handle duplicates at all
            //   and will let you insert pretty much everything, we'll see
            //   later for this; but this is bad, we cannot deduplicate what
            //   are old sites urls and expired urls from us
            if ($outdated) {
                $this
                    ->database
                    ->insert('ucms_seo_redirect')
                    ->fields([
                        'nid'     => $nodeId,
                        'site_id' => $siteId,
                        'path'    => '/' . $outdated,
                        'expires' => (new \DateTime(self::DEFAULT_EXPIRE))->format('Y-m-d H:i:s'),
                    ])
                    ->execute()
                ;
            }

            if ($computed) {
                $computed = $this->deduplicate($nodeId, $siteId, $computed);

                $this
                    ->database
                    ->insert('ucms_seo_route')
                    ->fields([
                        'node_id'       => $nodeId,
                        'site_id'       => $siteId,
                        'route'         => $computed,
                        'is_protected'  => 0,
                        'is_outdated'   => 0,
                    ])
                    ->execute()
                ;
            }

            // Explicit commit
            unset($transaction);

            return $computed;

        } catch (\PDOException $e) {
            try {
                if ($transaction) {
                    $transaction->rollback();
                }
            } catch (\Exception $e2) {
                // You are fucked.
                watchdog_exception(__FUNCTION__, $e2);
            }

            throw $e;
        }
    }

    /**
     * Invalidate aliases with the given conditions
     *
     * @param array $conditions
     *   Keys are column names, values are either single value or an array of
     *   value to match to invalidate; allowed keys are:
     *     - node_id: one or more node identifiers
     *     - site_id: one or more site identifiers
     */
    public function invalidate(array $conditions)
    {
        if (empty($conditions)) {
            throw new \InvalidArgumentException("cannot invalidate aliases with no conditions");
        }

        $query = $this
            ->database
            ->update('ucms_seo_route')
            ->fields(['is_outdated' => 1])
            ->condition('is_protected', 0)
        ;

        foreach ($conditions as $key => $value) {
            switch ($key) {

                case 'node_id':
                    $query->condition('node_id', $value);
                    break;

                case 'site_id':
                    $query->condition('site_id', $value);
                    break;

                default:
                    throw new \InvalidArgumentException(sprintf("condition '%s' is not supported for aliases invalidation", $key));
            }
        }

        $query->execute();
    }

    /**
     * Invalidate aliases related with the given node
     *
     * @todo this will a few SQL indices
     *
     * @param int[] $nodeIdList
     */
    public function invalidateRelated($nodeIdList)
    {
        if (!$nodeIdList) {
            return;
        }

        $this
            ->database
            ->query("
                UPDATE {ucms_seo_route}
                SET
                    is_outdated = 1
                WHERE
                    is_outdated = 0
                    AND is_protected = 0
                    AND menu_id IS NOT NULL
                    AND menu_id IN (
                        SELECT
                            DISTINCT(i.menu_id)
                        FROM {umenu_item} i
                        WHERE
                            i.node_id IN (:nodeIdList)
                    )
            ", [':nodeIdList' => $nodeIdList])
        ;

        // This is sad, but when data is not consistent and menu identifier
        // is not set, we must wipe out the complete site cache instead, but
        // hopefully, it won't happen again once we'll have fixed the item
        // insertion.
        $this
            ->database
            ->query("
                UPDATE {ucms_seo_route}
                SET
                    is_outdated = 1
                WHERE
                    is_outdated = 0
                    AND is_protected = 0
                    AND menu_id IS NULL
                    AND site_id IN (
                        SELECT
                            DISTINCT(i.site_id)
                        FROM {umenu_item} i
                        WHERE
                            i.node_id IN (:nodeIdList)
                    )
            ", [':nodeIdList' => $nodeIdList])
        ;
    }

    /**
     * Set custom alias for
     *
     * @param int $nodeId
     * @param int $siteId
     * @param string $siteId
     */
    public function setCustomAlias($nodeId, $siteId, $alias)
    {
        $this
            ->database
            ->merge('ucms_seo_route')
            ->key([
                'node_id' => $nodeId,
                'site_id' => $siteId,
            ])
            ->fields([
                'route'        => $alias,
                'is_protected' => 1,
                'is_outdated'  => 0,
            ])
            ->execute()
        ;
    }

    /**
     * Remove custom alias for
     *
     * @param int $nodeId
     * @param int $siteId
     */
    public function removeCustomAlias($nodeId, $siteId)
    {
        $this
            ->database
            ->update('ucms_seo_route')
            ->condition('node_id', $nodeId)
            ->condition('site_id', $siteId)
            ->fields(['is_protected' => 0, 'is_outdated' => 1])
            ->execute()
        ;
    }

    /**
     * Is the current path alias protected (i.e. manually set by user)
     *
     * @param int $nodeId
     * @param int $siteId
     *
     * @return bool
     */
    public function isPathAliasProtected($nodeId, $siteId)
    {
        return (bool)$this
            ->database
            ->query(
                "SELECT is_protected FROM {ucms_seo_route} WHERE node_id = :node AND site_id = :site",
                [':node' => $nodeId, ':site' => $siteId]
            )
            ->fetchField()
        ;
    }

    /**
     * Get alias for node on site
     *
     * Internally, if no alias was already computed, this will recompute
     * and store it into a custom route match table.
     *
     * @param int $nodeId
     * @param int $siteId
     *
     * @return string
     */
    public function getPathAlias($nodeId, $siteId)
    {
        $route = $this
            ->database
            ->query(
                "SELECT route, is_outdated FROM {ucms_seo_route} WHERE node_id = :node AND site_id = :site",
                [':node' => $nodeId, ':site' => $siteId]
            )
            ->fetch()
        ;

        if (!$route || $route->is_outdated) {
            return $this->computeAndStorePathAlias($nodeId, $siteId, ($route ? $route->route : null));
        } else {
            return $route->route;
        }
    }

    /**
     * Match path on given site
     *
     * @todo this one will be *very* hard to compute without umenu taking
     *   care of it for us;
     *
     * @param string $alias
     * @param int $siteId
     *
     * @return null|int
     *   The node identifier if found
     */
    public function matchPath($alias, $siteId)
    {
        $nodeId = $this
            ->database
            ->query(
                "SELECT node_id FROM {ucms_seo_route} WHERE route = :route AND site_id = :site",
                [':route' => $alias, ':site' => $siteId]
            )
            ->fetchField()
        ;

        return $nodeId ? ((int)$nodeId) : null;
    }
}
