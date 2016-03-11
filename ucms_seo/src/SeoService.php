<?php

namespace MakinaCorpus\Ucms\Seo;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\node\NodeInterface;

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
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * Default constructor
     *
     * @param EntityManager $entiyManager
     * @param AliasManagerInterface $aliasManager
     * @param AliasStorageInterface $aliasStorage
     * @param \DatabaseConnection $db
     */
    public function __construct(
        EntityManager $entiyManager,
        AliasManagerInterface $aliasManager,
        AliasStorageInterface $aliasStorage,
        \DatabaseConnection $db)
    {
        $this->entityManager = $entiyManager;
        $this->aliasManager = $aliasManager;
        $this->aliasStorage = $aliasStorage;
        $this->db = $db;
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
            $this->onAliasChange($node);
        } else {
            $this->onAliasRemove($node);
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
    public function nodeAliasesMerge($nodeAliases, $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED)
    {
        // We hope doing that in only 3 SQL queries, we can't do less than
        // that, first one load existing aliases, second insert missing,
        // while the third update the existing which have an expire date

        if (empty($nodeAliases)) {
            return;
        }

        $sourceMap = [];
        foreach ($nodeAliases as $id => $aliases) {
            $sourceMap['node/' . $id] = $id;
        }

        // 1 SQL query
        $r = $this
            ->db
            ->select('ucms_seo_alias', 'u')
            ->fields('u', ['pid', 'source', 'alias', 'expires'])
            ->condition('u.source', array_keys($sourceMap))
            ->condition('u.language', $langcode)
            ->execute()
        ;

        $expiring = [];

        foreach ($r as $row) {
            if (isset($sourceMap[$row->source])) {
                if ($row->expires) {
                    $expiring[] = $row->pid;
                }
                $nodeId = $sourceMap[$row->source];
                if (false !== ($index = array_search($row->alias, $nodeAliases[$nodeId]))) {
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
                ->fields(['source', 'alias', 'language'])
            ;
            foreach ($nodeAliases as $nodeId => $aliases) {
                foreach ($aliases as $alias) {
                    $q->values(['node/' . $nodeId, $alias, $langcode]);
                }
            }
            $q->execute();
        }
    }

    /**
     * From the given node, rebuild all its aliases and children aliases
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
     */
    public function onAliasChange(NodeInterface $node)
    {
        $nodeRoute  = 'node/' . $node->id();
        $langcode   = $node->language()->getId();

        $idList = $this
            ->db
            ->select('menu_links', 'l')
            ->fields('l', ['mlid'])
            ->condition('link_path', $nodeRoute)
            ->execute()
            ->fetchCol()
        ;

        if ($idList) {

            // Fetch all associated node aliases from the given menu link
            $nodeAliases = [];
            foreach ($idList as $id) {
                $nodeAliases = NestedArray::mergeDeep(
                    $nodeAliases,
                    $this->getLinkChildrenAliases($id)
                );
            }

            // Once merge, bulk update everything in there
            $this->nodeAliasesMerge($nodeAliases, $langcode);

        } else {
            $segment = $this->getNodeSegment($node);

            // Node has no attached menus, so just add the segment alias
            // as full alias if none exists
            if ($segment && !$this->aliasStorage->load(['langcode' => $langcode, 'source' => $nodeRoute])) {
                $this->aliasStorage->save($nodeRoute, $segment, $langcode);
            }
        }

        // @todo
        //   with the current algorithm we sadly can't find outdated
        //   node aliases and set an expire on them
    }

    /**
     * Node being removed means that everywhere it has been set as a menu
     * item must recompute the parenting tree, but we should also drop
     * all aliases of the node
     *
     * @param NodeInterface $node
     */
    public function onAliasRemove(NodeInterface $node)
    {
        $this->onAliasChange($node);
    }

    /**
     * On node post save event
     */
    public function onNodeSave(NodeInterface $node)
    {
        if (property_exists($node, 'ucms_seo_segment')) {
            $this->setNodeSegment($node, $node->ucms_seo_segment);
        } else if (!$node->isNew()) {
            $this->setNodeSegment($node, null);
        }
    }

    /**
     * On node delete event
     */
    public function onNodeDelete(NodeInterface $node)
    {
        $this->onAliasRemove($node);
    }
}