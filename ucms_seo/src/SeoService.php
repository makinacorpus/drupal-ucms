<?php

namespace MakinaCorpus\Ucms\Seo;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;

/**
 * Main access point for SEO information, all Drupal-7-ish stuff will be
 * masked behind this implementation (except the field itself) making it
 * easier to port later.
 *
 * @todo
 *   - for Drupal 8 porting, overrides the NodeRouteProvider object
 *
 * @see \Drupal\node\Entity\NodeRouteProvider
 */
class SeoService
{
    /**
     * Manage SEO parameters on all content
     */
    const PERM_SEO_CONTENT_ALL = 'ucms seo content all manage';

    /**
     * Manage SEO parameters on own content
     */
    const PERM_SEO_CONTENT_OWN = 'ucms seo content own manage';

    /**
     * Manage global SEO parameters
     */
    const PERM_SEO_GLOBAL = 'ucms seo global manage';

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var AliasManagerInterface
     */
    private $aliasManager;

    /**
     * @var AliasStorageInterface
     */
    private $aliasStorage;

    /**
     * @var SiteManager
     */
    private $siteManager;

    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * Default constructor
     *
     * @param EntityManager $entiyManager
     * @param AliasManagerInterface $aliasManager
     * @param AliasStorageInterface $aliasStorage
     * @param SiteManager $siteManager
     * @param \DatabaseConnection $db
     */
    public function __construct(
        EntityManager $entiyManager,
        AliasManagerInterface $aliasManager,
        AliasStorageInterface $aliasStorage,
        SiteManager $siteManager,
        \DatabaseConnection $db)
    {
        $this->entityManager = $entiyManager;
        $this->aliasManager = $aliasManager;
        $this->aliasStorage = $aliasStorage;
        $this->siteManager = $siteManager;
        $this->db = $db;
    }

    /**
     * Get alias storage
     *
     * @return AliasStorageInterface
     */
    public function getAliasStorage()
    {
        return $this->aliasStorage;
    }

    /**
     * Get node storage
     *
     * @return EntityStorageInterface
     */
    protected function getNodeStorage()
    {
        return $this->entityManager->getStorage('node');
    }

    /**
     * Get node identifier from route
     *
     * @param string $route
     *
     * @return int
     *   Or null if it's not a node route
     */
    protected function getLinkNodeId($route)
    {
        if (false === ($pos = strpos($route, '/'))) {
            return;
        }

        $id = substr($route, $pos + 1);

        if (is_numeric($id)) {
            return (int)$id;
        }
    }

    /**
     * Get node associate to given link route
     *
     * @param string $route
     *
     * @return NodeInterface
     */
    protected function getLinkNode($route)
    {
        return $this
            ->getNodeStorage()
            ->load(
                $this->getLinkNode($route)
            )
        ;
    }

    /**
     * Get aliases for the given nodes
     *
     * @param int[] $nodeIdList
     *
     * @return string[]
     *   Keys are node identifiers, values are alias segment for each node,
     *   order is no guaranted, non existing nodes or node without a segment
     *   will be excluded from the return array
     */
    protected function getNodeAliasMap($nodeIdList)
    {
        return $this
            ->db
            ->select('ucms_seo_node', 'n')
            ->fields('n', ['nid', 'alias_segment'])
            ->condition('n.nid', $nodeIdList)
            ->execute()
            ->fetchAllKeyed()
        ;
    }

    /**
     * Can user edit SEO parameters for site
     *
     * @param AccountInterface $account
     * @param Site $site
     */
    public function userCanEditSiteSeo(AccountInterface $account, Site $site)
    {
        $access = $this->siteManager->getAccess();

        return
            $access->userCanView($account, $site) && (
                $account->hasPermission(SeoService::PERM_SEO_GLOBAL) ||
                $account->hasPermission(SeoService::PERM_SEO_CONTENT_ALL) ||
                $access->userIsWebmaster($account, $site)
            )
        ;
    }

    /**
     * Can user edit SEO parameters for node
     *
     * @param AccountInterface $account
     * @param NodeInterface $node
     */
    public function userCanEditNodeSeo(AccountInterface $account, NodeInterface $node)
    {
        return
            ($account->hasPermission(SeoService::PERM_SEO_CONTENT_OWN) && $node->access('update', $account)) ||
            ($account->hasPermission(SeoService::PERM_SEO_CONTENT_ALL) && $node->access('view', $account))
        ;
    }

    /**
     * Set node meta information
     *
     * @param NodeInterface $node
     * @param string[] $values
     *   Keys are meta tag title, values are meta tag content
     */
    public function setNodeMeta(NodeInterface $node, $values = [])
    {
        $sqlValues = [];

        foreach ($values as $key => $value) {

            if (empty($value)) {
                $value = null;
            }

            switch ($key) {

                case 'title':
                case 'description':
                    $sqlValues['meta_' . $key] = $value;
                    break;

                default:
                    continue;
            }
        }

        if (empty($values)) {
            return;
        }

        $this
            ->db
            ->update('ucms_seo_node')
            ->fields($sqlValues)
            ->condition('nid', $node->id())
            ->execute()
        ;

        // @todo clear page cache
    }

    /**
     * Get node meta information
     *
     * @param NodeInterface $node
     *
     * @return string[] $values
     *   Keys are meta tag title, values are meta tag content
     */
    public function getNodeMeta(NodeInterface $node)
    {
        return (array)$this->db->query("SELECT meta_title AS title, meta_description AS description FROM {ucms_seo_node} WHERE nid = ?", [$node->id()])->fetchAssoc();
    }

    /**
     * Get node canonical alias
     *
     * @param NodeInterface $node
     * @param string $langcode
     */
    public function getNodeCanonicalAlias(NodeInterface $node, $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED)
    {
        // We need to directly query the path alias table from here.
        $query = $this
            ->db
            ->select('ucms_seo_alias', 'u')
            ->fields('u', ['alias', 'site_id'])
            ->condition('node_id', $node->id())
        ;

        // Where the magic happens, if no canonical is present, this query
        // actually does reproduce the SeoAliasStorage::lookupPathAlias() order
        // and ensure we have consistent aliases and canonicals all over the
        // place. The only thing that's different is that we won't filter by
        // site since we will fetch it from the result itself, or from the
        // node if no site found.
        $query->orderBy('u.is_canonical', 'DESC');
        $query->orderBy('u.priority', 'DESC');

        // If language is not specified, attempt with the node one
        if (LanguageInterface::LANGCODE_NOT_SPECIFIED === $langcode) {
            $langcode = $node->language; // @todo Drupal 8
        }

        // Language order is less important than site itself.
        if (LanguageInterface::LANGCODE_NOT_SPECIFIED === $langcode) {
            $langcodeList = [$langcode];
        } else {
            $langcodeList = [$langcode, LanguageInterface::LANGCODE_NOT_SPECIFIED];
            if (LanguageInterface::LANGCODE_NOT_SPECIFIED < $langcode) {
                $query->orderBy('u.language', 'DESC');
            } else {
                $query->orderBy('u.language', 'ASC');
            }
        }

        return $query
            ->orderBy('u.expires', 'IS NULL DESC')
            ->orderBy('u.pid', 'DESC')
            ->range(0, 1)
            ->condition('u.language', $langcodeList)
            ->execute()
            ->fetch()
        ;
    }

    /**
     * Get node canonical URL
     *
     * @param NodeInterface $node
     * @param string $langcode
     */
    public function getNodeCanonical(NodeInterface $node, $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED)
    {
        $route  = 'node/' . $node->id();
        $row = $this->getNodeCanonicalAlias($node, $langcode);

        if (!$row) {
            // No alias at all means that the canonical is the node URL in the
            // current site, I am sorry I can't provide more any magic here...
            return url($route, ['absolute' => true]);
        }

        $site = null;
        $storage = $this->siteManager->getStorage();

        if ($row->site_id) {
            $site = $storage->findOne($row->site_id);
        } else {
            // There is no site, let's fetch the node one, the first, original
            // one deserves to actually be the canonical.
            if ($node->site_id) {
                $storage->findOne($node->site_id);
            } else if ($this->siteManager->hasContext()) {
                $site = $this->siteManager->getContext();
            } else {
                // Attempt to find the very first site the node was in and just
                // use that one.
                // @todo DO IT BITCH !
            }
        }

        if (!$site) {
            return url($row->alias, ['absolute' => true]);
        }

        return ($GLOBALS['is_https'] ? 'https://' : 'http://') . $site->http_host . '/' . $row->alias;
    }

    /**
     * Get node alias segment
     *
     * @param NodeInterface $node
     *
     * @return string
     */
    public function getNodeSegment(NodeInterface $node)
    {
        if ($node->isNew()) {
            return;
        }

        $map = $this->getNodeAliasMap([$node->id()]);

        if ($map) {
            return reset($map);
        }
    }

    /**
     * Normalize given URL segment
     *
     * @param string $value
     * @param int $maxLength
     *
     * @return string
     */
    public function normalizeSegment($value, $maxLength = 255)
    {
        // Transliterate first
        if (class_exists('URLify')) {
            $value = \URLify::filter($value, $maxLength);
        }

        // Only lowercase characters
        $value = strtolower($value);

        // All special characters to be replaced by '-' and doubles stripped
        // to only one occurence
        $value = preg_replace('/[^a-z0-9\-]+/', '-', $value);
        $value = preg_replace('/-+/', '-', $value);

        // Trim leading and trailing '-' characters
        $value = trim($value, '-');

        // @todo stopwords

        return $value;
    }

    /**
     * Change the node segment
     *
     * @param NodeInterface $node
     * @param string $segment
     *   New node segment
     * @param string $previous
     *   If by any chance you are sure you know the previous one, set it here
     *   to save a SQL query
     */
    public function setNodeSegment(NodeInterface $node, $segment, $previous = null)
    {
        if (!$previous) {
            $previous = $this->getNodeSegment($node);
        }
        if (empty($segment)) {
            $segment = null;
        }

        if ($previous === $segment) {
            return; // Nothing to do
        }

        $this
            ->db
            ->merge('ucms_seo_node')
            ->key(['nid' => $node->id()])
            ->fields(['alias_segment' => $segment])
            ->execute()
        ;

        if (empty($segment)) {
            $this->onAliasRemove($node);
        } else {
            $this->onAliasChange($node);
        }
    }

    /**
     * Get alias associated to menu link
     *
     * This function will do some recursion over menu links table queries so
     * please use it wisely, only for rebuilding.
     *
     * This will break as soon as any found menu are not pointing to a node,
     * including the current menu given as parameter.
     *
     * @param int $id
     *   Menu link identifier
     *
     * @return string
     */
    public function getLinkAlias($id)
    {
        $nodeIdList = [];
        $segments   = [];

        do {
            $q = $this->db->select('menu_links', 'l');
            $q->addField('l', 'plid', 'parent');
            $q->addField('l', 'link_path', 'route');

            $item = $q->condition('l.mlid', $id)->range(0, 1)->execute()->fetch();

            if ($item) {
                if ($nodeId = $this->getLinkNodeId($item->route)) {
                    array_unshift($nodeIdList, $nodeId);
                    $id = $item->parent; // Next iteration
                } else {
                    // The menu is not a node, just fetch its rightful alias
                    // and prefix the segments with, then return
                    $segments[] = $this->aliasManager->getAliasByPath($item->route);
                    break;
                }
            }
        } while ($item);

        if ($nodeIdList) {
            $map = $this->getNodeAliasMap($nodeIdList);
            // Build the segment list
            foreach ($nodeIdList as $nodeId) {
                if (isset($map[$nodeId])) {
                    $segments[] = $map[$nodeId];
                } else {
                    $segments[] = 'n' . $nodeId; // Fallback.
                }
            }
        }

        return implode('/', $segments);
    }

    /**
     * Get menu links aliases for children
     *
     * This function will do some recursion over menu links table queries so
     * please use it wisely, only for rebuilding.
     *
     * This will break as soon as any found menu are not pointing to a node,
     * including the current menu given as parameter.
     *
     * @param int $id
     *   Menu link identifier
     * @param string $prefix
     *   If the menu alias has already been computed (during recursion) this
     *   will be set
     *
     * @return string[]
     *   Keys are node identifiers, values are new aliases
     */
    public function getLinkChildrenAliases($id, $prefix = null)
    {
        $ret = [];

        // In this function, node identifiers will be associated to menu links
        // identifiers, so that we can later do a fast pseudo topological browse
        // of the links tree and build aliases
        $linkMap = [];

        // First collect all children recursively
        $q = $this->db->select('menu_links', 'l');
        $q->addField('l', 'mlid', 'id');
        $q->addField('l', 'plid', 'parent');
        $q->addField('l', 'has_children', 'hasChildren');
        $q->addField('l', 'link_path', 'route');
        $or = db_or();
        // Clever girl (1).
        foreach (range(1, 9) as $i) {
            $or->condition('l.p' . $i, $id);
        }
        $itemList = $q->condition($or)->execute()->fetchAllAssoc('id');

        if (empty($itemList)) {
            return [];
        }

        // Then collect all node identifiers associated to those
        foreach ($itemList as $index => $item) {
            if ($nodeId = $this->getLinkNodeId($item->route)) {
                $linkMap[$item->id] = $nodeId;
                $itemList[$item->id]->nodeId = $nodeId;
            } else {
                unset($itemList[$index]);
            }
        }

        // Load all at once, and prey
        $map = $this->getNodeAliasMap($linkMap);

        foreach ($linkMap as $index => $nodeId) {
            if (isset($map[$nodeId])) {
                $linkMap[$index] = $map[$nodeId];
            } else {
                $linkMap[$index] = 'n' . $nodeId; // Fallback.
            }
        }

        // Don't forget to add the one we had as function parameter
        $linkMap[$id] = $prefix ? $prefix : $this->getLinkAlias($id);

        // And now the hard part: we need to proceed to a topological sort on
        // loaded item list, match them to the node identifiers, and build all
        // of their aliases depending on parent node aliases.
        foreach ($itemList as $item) {
            $segments = [];
            $current = $item;
            do {
                array_unshift($segments, $linkMap[$current->id]);
                if (isset($itemList[$current->parent])) {
                    $current = $itemList[$current->parent];
                } else {
                    break;
                }
            } while ($current);
            $ret[$item->nodeId][] = implode('/', $segments);
        }

        // For lucky little bastards that read the function till the end:
        //   (1) Muldoon: [Just before he gets attacked by a raptor].
        return $ret;
    }

    /**
     * Bulk merge node aliases
     *
     * @param string[][] $nodeAliases
     *   First dimension keys are node identifiers, and values are
     *   arrays of actual node aliases
     */
    protected function nodeAliasesMerge($nodeAliases, $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED, $siteId)
    {
        // We hope doing that in only 3 SQL queries, we can't do less than
        // that, first one load existing aliases, second insert missing,
        // while the third update the existing which have an expire date

        if (empty($nodeAliases)) {
            return;
        }

        // 1 SQL query
        $r = $this
            ->db
            ->select('ucms_seo_alias', 'u')
            ->fields('u', ['pid', 'source', 'alias', 'expires', 'node_id'])
            ->condition('u.node_id', array_keys($nodeAliases))
            ->condition('u.language', $langcode)
            ->condition('u.site_id', $siteId)
            ->execute()
        ;

        $expiring = [];

        foreach ($r as $row) {
            if (isset($nodeAliases[$row->node_id])) {
                if ($row->expires) {
                    $expiring[] = $row->pid;
                }
                $nodeId = $row->node_id;
                if (!empty($nodeAliases[$nodeId]) && false !== ($index = array_search($row->alias, $nodeAliases[$nodeId]))) {
                    // Unmark the alias for insertion
                    unset($nodeAliases[$nodeId][$index]);
                    if (empty($nodeAliases[$nodeId])) {
                        unset($nodeAliases[$nodeId]);
                    }
                }
            }
        }

        if ($expiring) {
            // 2 SQL queries
            $this
                ->db
                ->update('ucms_seo_alias')
                ->condition('pid', $expiring)
                ->fields('expires', null)
                ->execute()
            ;
        }

        if (!empty($nodeAliases)) {
            // 3 SQL queries
            $q = $this
                ->db
                ->insert('ucms_seo_alias')
                ->fields(['source', 'alias', 'language', 'site_id', 'node_id', 'priority' => Alias::PRIORITY_DEFAULT])
            ;
            foreach ($nodeAliases as $nodeId => $aliases) {
                foreach ($aliases as $alias) {
                    $q->values(['node/' . $nodeId, $alias, $langcode, $siteId, $nodeId]);
                }

                // Bad thing here, we need to manually clear the path cache for
                // each node one by one
                $this->aliasManager->cacheClear('node/' . $nodeId);
            }
            $q->execute();
        }
    }

    /**
     * Find and update the canonical link for node
     *
     * It won't return anything because it will just update the alias table and
     * Drupal magic will do the rest
     *
     * @param object|array $alias
     */
    public function setCanonicalForAlias($alias)
    {
        if (!is_object($alias)) {
            $alias = (object)$alias;
        }

        $this
            ->db
            ->update('ucms_seo_alias')
            ->fields(['is_canonical' => 0])
            ->condition(
                'source',
                $this->db->escapeLike($alias->source),
                'LIKE'
            )
            ->condition('language', $alias->language)
            ->execute()
        ;

        // Use MERGE here to ensure the alias will be correctly created
        $this
            ->db
            ->merge('ucms_seo_alias')
            ->key([
                'source'    => $alias->source,
                'alias'     => $alias->alias,
                'language'  => $alias->language,
            ])
            ->fields([
                'is_canonical'  => 1,
            ])
            ->execute()
        ;
    }

    /**
     * Find and update the canonical link for node
     *
     * It won't return anything because it will just update the alias table and
     * Drupal magic will do the rest
     *
     * @param NodeInterface $node
     * @param Site $site
     * @param string $alias
     * @param string $langcode
     */
    public function updateCanonicalForNode(NodeInterface $node, $alias, Site $site, $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED)
    {
        $route  = 'node/' . $node->id();

        $this
            ->db
            ->update('ucms_seo_alias')
            ->fields(['is_canonical' => 0])
            ->condition('node_id', $node->id(), 'LIKE')
            ->condition('language', $langcode)
            ->execute()
        ;

        // Use MERGE here to ensure the alias will be correctly created
        $this
            ->db
            ->merge('ucms_seo_alias')
            ->key([
                'source'    => $route,
                'alias'     => $alias,
                'language'  => $langcode,
                'site_id'   => $site->getId(),
            ])
            ->fields([
                'is_canonical'  => true,
                // Enfore node identifier, because you never know.
                'node_id'       => $node->id(),
            ])
            ->execute()
        ;
    }

    /**
     * From the given node, rebuild all its aliases and children aliases
     *
     * FIXME: THIS MAY CAUSE SERIOUS TROUBLES WHEN CHANGING NODE SEGMENT
     * FOR NODES REFERENCED IN MANY MENUS
     *
     * All node aliases for each menu entry it is into must exists in the
     * menu tree, no matter how many they are, for the sake of consistency
     * one and only one of this aliases will be marked as canonical, and
     * will be the first one the node has been set.
     *
     * Now, how aliases are built: for example, lets say you have nodes,
     * with their respective aliases (identifiers don't matter):
     *
     *   node/1 -> foo
     *   node/2 -> bar
     *   node/3 -> baz
     *   node/4 -> john
     *   node/5 -> smith
     *
     * and the following main menu tree:
     *   - node/1
     *     - node/2
     *       - node/3
     *     - node/4
     *       - node/5
     *         - node/1 (this is a trap)
     *     - node/3 (this is a trap too)
     *
     * then you'll get the following path aliases:
     *
     *   node/1 -> foo AND foo/john/smith/foo (remember the first trap)
     *   node/2 -> foo/bar
     *   node/3 -> foo/bar/baz AND foo/baz (remember the second trap)
     *   node/4 -> foo/john
     *   node/5 -> foo/john/smith
     *
     * Every menu path will be extracted and associated aliases rebuilt
     * if changed, and all sub menu trees will too, this means that whenever
     * a node is unpublished for example, all its children aliases will looose
     * one level in their path alias.
     *
     * Outdate aliases will be kept, with a set lifetime, and garbage collected
     * over time in order to avoid the url_alias table to grow too much.
     *
     * @param NodeInterface $node
     * @param string $menuName
     *   To restrict to a single menu, if you are sure that the node segment
     *   itself has NOT been changed, if it did, leave this to null, everything
     *   will be very slow, but it is necessary
     */
    public function onAliasChange(NodeInterface $node, $menuName = null)
    {
        $nodeRoute  = 'node/' . $node->id();
        $langcode   = $node->language()->getId();

        // Fetch all menu links associated to this node in order to rebuild
        // their parenting tree with the new alias, if any changed
        $q = $this
            ->db
            ->select('menu_links', 'l')
            ->fields('l', ['mlid'])
            ->fields('m', ['site_id']);
        ;
        // We cannot deal aliases that are not in site context
        $q->join('umenu', 'm', "m.name = l.menu_name AND m.site_id IS NOT NULL");
        if ($menuName) {
            $q->condition('l.menu_name', $menuName);
        }
        $idMap = $q->condition('l.link_path', $nodeRoute)->execute()->fetchAllKeyed();

        if ($idMap) {

            // Fetch all associated node aliases from the given menu link
            $nodeAliases = [];
            foreach ($idMap as $id => $siteId) {
                if (!array_key_exists($siteId, $nodeAliases)) {
                    $nodeAliases[$siteId] = [];
                }
                $nodeAliases[$siteId] = NestedArray::mergeDeepArray(
                    [
                        $nodeAliases[$siteId],
                        $this->getLinkChildrenAliases($id),
                    ],
                    true
                );
            }

            // Once merge, bulk update everything in there
            // @todo this will probably be terrible for performances
            foreach ($nodeAliases as $siteId => $aliases) {
                $this->nodeAliasesMerge($aliases, $langcode, $siteId);
            }
        }

        // Ensure node primary alias
        $this->ensureNodePrimaryAlias($node);
    }

    /**
     * Node being removed means that everywhere it has been set as a menu
     * item must recompute the parenting tree, but we should also drop
     * all aliases of the node
     *
     * @param NodeInterface $node
     * @param string $menuName
     *   To restrict to a single menu
     */
    public function onAliasRemove(NodeInterface $node, $menuName = null)
    {
        if ($menuName) {
            // This will work, since the whole menu will be recomputed
            $this->onAliasChange($node, $menuName);
        } else {
            // @todo but this won't work as we'd expect... need to investigate
            // further into this
            $this->onAliasChange($node, null);
        }
    }

    /**
     * From the given site, ensure that all nodes in it have a primary alias
     *
     * @see ::ensureNodePrimaryAlias()
     *   For the equivalent method that ensure all aliases on all sites for
     *   a single node
     *
     * @param int $siteId
     * @param string $nodeIdList
     *   If given, restrict the bulk operation to given node list
     */
    public function ensureSitePrimaryAliases($siteId, array $nodeIdList = null)
    {
        // HUGE YEAH! CONCAT() exists in both MySQL and PostgreSQL and are
        // compatible altogether!!!
        if ($nodeIdList) {
            $this
                ->db
                ->query("
                    INSERT INTO {ucms_seo_alias}
                        (source, alias, language, site_id, node_id, priority)
                    SELECT
                        CONCAT('node', '/', s.nid), s.alias_segment, n.language, r.site_id, s.nid, :priority
                    FROM {ucms_seo_node} s
                    JOIN {node} n ON n.nid = s.nid
                    JOIN {ucms_site_node} r
                        ON r.site_id = :siteId
                        AND r.nid = s.nid
                    WHERE
                        s.nid IN (:nidList)
                        AND NOT EXISTS (
                            SELECT 1 FROM {ucms_seo_alias} a
                            WHERE
                                a.node_id = n.nid
                                AND a.site_id = r.site_id
                        )
                ", [
                    ':priority'   => Alias::PRIORITY_LOW,
                    ':siteId'     => $siteId,
                    ':nidList'    => $nodeIdList,
                ])
            ;
        } else {
            $this
                ->db
                ->query("
                    INSERT INTO {ucms_seo_alias}
                        (source, alias, language, site_id, node_id, priority)
                    EXPLAIN
                    SELECT
                        CONCAT('node', '/', s.nid), s.alias_segment, n.language, r.site_id, s.nid, :priority
                    FROM {ucms_seo_node} s
                    JOIN {node} n ON n.nid = s.nid
                    JOIN {ucms_site_node} r
                        ON r.site_id = :siteId
                        AND r.nid = s.nid
                    WHERE
                        NOT EXISTS (
                            SELECT 1 FROM {ucms_seo_alias} a
                            WHERE
                                a.node_id = n.nid
                                AND a.site_id = r.site_id
                        )
                ", [
                    ':siteId'   => $siteId,
                    ':priority' => Alias::PRIORITY_LOW,
                ])
            ;
        }

        $this->aliasManager->cacheClear();
    }

    /**
     * From the given node, ensures at least one alias exists for it on all
     * sites it is related, leave alone aliases when a menu exists
     *
     * @see ::ensureSitePrimaryAliases()
     *   For the equivalent method that ensure aliases of a list of nodes for
     *   a single site
     *
     * @param NodeInterface $node
     */
    public function ensureNodePrimaryAlias(NodeInterface $node)
    {
        $nodeRoute  = 'node/' . $node->id();
        $segment    = $this->getNodeSegment($node);
        $langcode   = $node->language()->getId();

        // If alias already exists, remove its potential expiry date so it gets
        // selected instead of others
        if ($segment) {
            $this
                ->db
                ->query("
                    UPDATE {ucms_seo_alias}
                    SET
                        expires = NULL
                    WHERE
                        node_id = :nid
                        AND alias = :alias
                        AND expires IS NOT NULL
                ", [
                    ':alias'      => $segment,
                    ':nid'        => $node->id(),
                ])
            ;

            // First query will set alias for nodes wherever it does not exists
            $this
                ->db
                ->query("
                    INSERT INTO {ucms_seo_alias}
                        (source, alias, language, site_id, node_id, priority)
                    SELECT
                        :source1, :alias1, :language, s.site_id, s.nid, :priority1
                    FROM {ucms_site_node} s
                    WHERE
                        s.nid = :nid
                        AND NOT EXISTS (
                            SELECT 1 FROM {ucms_seo_alias} e
                            WHERE
                                e.site_id = s.site_id AND (
                                    e.alias = :alias2
                                    OR (e.source = :source2 AND e.priority > :priority2)
                                )
                        )
                ", [
                    ':alias1'     => $segment,
                    ':alias2'     => $segment,
                    ':language'   => $langcode,
                    ':nid'        => $node->id(),
                    ':priority1'  => Alias::PRIORITY_LOW,
                    ':priority2'  => Alias::PRIORITY_LOW,
                    ':source1'    => $nodeRoute,
                    ':source2'    => $nodeRoute,
                ])
            ;

            // Considering that the segment just changed, we also need to update
            // it where it was generated previously
            $this
                ->db
                ->query("
                    UPDATE {ucms_seo_alias}
                    SET
                        expires = :expiry
                    WHERE
                        node_id = :nid
                        AND alias <> :alias
                        AND priority = :priority
                ", [
                    ':alias'      => $segment,
                    ':expiry'     => (new \DateTime(Alias::EXPIRY))->format('Y-m-d H:i:s'),
                    ':nid'        => $node->id(),
                    ':priority'   => Alias::PRIORITY_LOW,
                ])
            ;
        } else {
            // Just mark as expiring everything
            $this
                ->db
                ->query("
                    UPDATE {ucms_seo_alias}
                    SET
                        expires = :expiry
                    WHERE
                        node_id = :nid
                        AND priority = :priority
                ", [
                    ':expiry'     => (new \DateTime(Alias::EXPIRY))->format('Y-m-d H:i:s'),
                    ':nid'        => $node->id(),
                    ':priority'   => Alias::PRIORITY_LOW,
                ])
            ;
        }

        $this->aliasManager->cacheClear();
    }

    /**
     * On node post save event
     */
    public function onNodeSave(NodeInterface $node)
    {
        $segment = null;

        if (property_exists($node, 'ucms_seo_segment') && !empty($node->ucms_seo_segment)) {
            $segment = $node->ucms_seo_segment;
        } else {
            // Automatically generate the first segment version from the node
            // title, force small length when not driven by user input
            $title = $node->getTitle();
            if ($title) {
                $segment = $this->normalizeSegment($title, 60);
            }
        }

        $this->setNodeSegment($node, $segment);
    }

    /**
     * On node delete event
     */
    public function onNodeDelete(NodeInterface $node)
    {
        $this->onAliasRemove($node);
    }
}
