<?php

namespace MakinaCorpus\Ucms\Seo;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use MakinaCorpus\ACL\Impl\Symfony\AuthorizationAwareInterface;
use MakinaCorpus\ACL\Impl\Symfony\AuthorizationAwareTrait;
use MakinaCorpus\ACL\Permission;
use MakinaCorpus\Ucms\Seo\Path\AliasManager;
use MakinaCorpus\Ucms\Seo\Path\RedirectStorageInterface;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use MakinaCorpus\Ucms\Site\SiteState;

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
class SeoService implements AuthorizationAwareInterface
{
    use AuthorizationAwareTrait;

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
     * @var AliasManager
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
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * Default constructor
     *
     * @param EntityManager $entiyManager
     * @param AliasManagerInterface $aliasManager
     * @param AliasStorageInterface $aliasStorage
     * @param RedirectStorageInterface $redirectStorage
     * @param SiteManager $siteManager
     * @param \DatabaseConnection $db
     */
    public function __construct(
        EntityManager $entiyManager,
        AliasManager $aliasManager,
        AliasStorageInterface $aliasStorage,
        RedirectStorageInterface $redirectStorage,
        SiteManager $siteManager,
        \DatabaseConnection $db)
    {
        $this->entityManager = $entiyManager;
        $this->aliasManager = $aliasManager;
        $this->aliasStorage = $aliasStorage;
        $this->redirectStorage = $redirectStorage;
        $this->siteManager = $siteManager;
        $this->db = $db;
    }

    /**
     * Clear alias cache, if any
     */
    public function clearCache()
    {
    }

    /**
     * Get alias storage
     *
     * @return AliasManager
     */
    public function getAliasManager()
    {
        return $this->aliasManager;
    }

    /**
     * Get redirect storage
     *
     * @return RedirectStorageInterface
     */
    public function getRedirectStorage()
    {
        return $this->redirectStorage;
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
        return
            $this->isGranted(Permission::VIEW, $site, $account) && (
                $account->hasPermission(SeoService::PERM_SEO_GLOBAL) ||
                $account->hasPermission(SeoService::PERM_SEO_CONTENT_ALL) ||
                $this->siteManager->getAccess()->userIsWebmaster($account, $site)
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
     * Get node canonical alias for the current site
     *
     * @param NodeInterface $node
     *
     * @return null|string
     */
    public function getNodeLocalCanonical(NodeInterface $node)
    {
        if ($this->siteManager->hasContext()) {
            return $this->aliasManager->getPathAlias($node->id(), $this->siteManager->getContext()->getId());
        }
    }

    /**
     * Get node canonical URL
     *
     * @param NodeInterface $node
     * @param string $langcode
     *
     * @return null|string
     */
    public function getNodeCanonical(NodeInterface $node)
    {
        $storage = $this->siteManager->getStorage();

        // Very fist site is the right one for canonical URL
        if ($node->site_id) {
            $site = $storage->findOne($node->site_id);
            if ($site->getState() !== SiteState::ON) {
                $site = null;
            }
        }

        // If no site, fetch on the current site
        if (!$site) {
            if ($this->siteManager->hasContext()) {
                $site = $this->siteManager->getContext();
            }
        }

        $alias = $this->aliasManager->getPathAlias($node->id(), $site->getId());

        if (!$alias) {
            // No alias at all means that the canonical is the node URL in the
            // current site, I am sorry I can't provide more any magic here...
            return url('node/' . $node->id(), ['absolute' => true]);
        }

        return $this->siteManager->getUrlGenerator()->generateUrl($site, $alias, ['absolute' => true], true);
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
    public function normalizeSegment($value, $maxLength = UCMS_SEO_SEGMENT_TRIM_LENGTH)
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
        return null;
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
        // Do something
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
        // Do something
    }

    /**
     * Within the given site, change all aliases from the given node to another
     *
     * Please be warned that WE DO NOT CHECK FOR CONSTRAINTS becaue it is only
     * supposed to happen at node insert time.
     *
     * @param int $siteId
     * @param int $previousNodeId
     * @param int $nextNodeId
     */
    public function replaceNodeAliases($siteId, $previousNodeId, $nextNodeId)
    {
        // Do something
    }
}
