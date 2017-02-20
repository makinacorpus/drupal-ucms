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
 * @todo handle invalidation
 * @todo deduplicate
 * @todo last but not least, we need a way to trick drupal path alias
 *   manager that our aliases are valid aliases for himself, would be
 *   good not to rely upon the path alias manager
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
        // @todo me like one of your french girls
        return $alias;
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

        if ($route->is_outdated || !$route) {
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
