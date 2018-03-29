<?php

namespace MakinaCorpus\Ucms\Seo;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use MakinaCorpus\ACL\Permission;
use MakinaCorpus\ACL\Bridge\Symfony\AuthorizationAwareInterface;
use MakinaCorpus\ACL\Bridge\Symfony\AuthorizationAwareTrait;
use MakinaCorpus\Ucms\Seo\Path\AliasCacheLookup;
use MakinaCorpus\Ucms\Seo\Path\AliasManager;
use MakinaCorpus\Ucms\Seo\Path\RedirectStorageInterface;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;
use MakinaCorpus\Umenu\Menu;

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

    private $aliasManager;
    private $aliasCacheLookup;
    private $siteManager;
    private $database;
    private $authorizationChecker;
    private $nodeTypeBlacklist;
    private $shareCanonicalAcrossSites = false;

    /**
     * Default constructor
     */
    public function __construct(
        AliasManager $aliasManager,
        AliasCacheLookup $aliasCacheLookup,
        RedirectStorageInterface $redirectStorage,
        SiteManager $siteManager,
        \DatabaseConnection $database,
        array $nodeTypeBlacklist = null,
        ?bool $shareCanonicalAcrossSites = null)
    {
        $this->aliasManager = $aliasManager;
        $this->aliasCacheLookup = $aliasCacheLookup;
        $this->redirectStorage = $redirectStorage;
        $this->siteManager = $siteManager;
        $this->database = $database;

        if (null === $nodeTypeBlacklist) {
            // @todo tainted
            $nodeTypeBlacklist = variable_get('ucms_seo_node_type_blacklist', []);
        }
        $this->nodeTypeBlacklist = array_flip($nodeTypeBlacklist);

        if (null === $shareCanonicalAcrossSites) {
            // @todo tainted
            $shareCanonicalAcrossSites = variable_get('ucms_seo_share_canonical', false);
        }
        $this->shareCanonicalAcrossSites = $shareCanonicalAcrossSites;
    }

    /**
     * Clear alias cache, if any
     */
    public function clearCache(): void
    {
    }

    /**
     * Get alias storage
     */
    public function getAliasManager() : AliasManager
    {
        return $this->aliasManager;
    }

    /**
     * Get alias cache lookup service
     */
    public function getAliasCacheLookup() : AliasCacheLookup
    {
        return $this->aliasCacheLookup;
    }

    /**
     * Get redirect storage
     */
    public function getRedirectStorage() : RedirectStorageInterface
    {
        return $this->redirectStorage;
    }

    /**
     * Get aliases for the given nodes
     *
     * @return string[]
     *   Keys are node identifiers, values are alias segment for each node,
     *   order is no guaranted, non existing nodes or node without a segment
     *   will be excluded from the return array
     */
    protected function getNodeAliasMap(array $nodeIdList) : array
    {
        return $this
            ->database
            ->select('ucms_seo_node', 'n')
            ->fields('n', ['nid', 'alias_segment'])
            ->condition('n.nid', $nodeIdList)
            ->execute()
            ->fetchAllKeyed()
        ;
    }

    /**
     * Can user edit SEO parameters for site
     */
    public function userCanEditSiteSeo(AccountInterface $account, Site $site) : bool
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
     */
    public function userCanEditNodeSeo(AccountInterface $account, NodeInterface $node) : bool
    {
        return
            !$this->isNodeBlacklisted($node) && (
                ($account->hasPermission(SeoService::PERM_SEO_CONTENT_OWN) && $node->access('update', $account)) ||
                ($account->hasPermission(SeoService::PERM_SEO_CONTENT_ALL) && $node->access('view', $account))
            )
        ;
    }

    /**
     * Is node type blacklisted for SEO handling
     */
    public function isNodeTypeBlacklisted(string $type) : bool
    {
        return $this->nodeTypeBlacklist && isset($this->nodeTypeBlacklist[$type]);
    }

    /**
     * Is node blacklisted for SEO handling
     */
    public function isNodeBlacklisted(NodeInterface $node) : bool
    {
        return $this->nodeTypeBlacklist && isset($this->nodeTypeBlacklist[$node->bundle()]);
    }

    /**
     * Set node meta information
     *
     * @param NodeInterface $node
     * @param string[] $values
     *   Keys are meta tag title, values are meta tag content
     */
    public function setNodeMeta(NodeInterface $node, array $values = []) : void
    {
        if ($this->isNodeBlacklisted($node)) {
            return;
        }

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
            ->database
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
    public function getNodeMeta(NodeInterface $node) : array
    {
        if ($this->isNodeBlacklisted($node)) {
            return [];
        }

        return (array)$this->database->query("SELECT meta_title AS title, meta_description AS description FROM {ucms_seo_node} WHERE nid = ?", [$node->id()])->fetchAssoc();
    }

    /**
     * Get node canonical alias for the current site
     */
    public function getNodeLocalCanonical(NodeInterface $node) : ?string
    {
        if ($this->isNodeBlacklisted($node)) {
            return 'node/' . $node->id();
        }

        if ($this->siteManager->hasContext()) {
            return $this->aliasManager->getPathAlias($node->id(), $this->siteManager->getContext()->getId());
        }
    }

    /**
     * Get node canonical URL
     */
    public function getNodeCanonical(NodeInterface $node) : ?string
    {
        if ($this->isNodeBlacklisted($node)) {
            return null;
        }

        $site = null;
        $alias = null;

        if ($this->shareCanonicalAcrossSites) {
            $storage = $this->siteManager->getStorage();

            // Very fist site is the right one for canonical URL
            if ($node->site_id) {
                $site = $storage->findOne($node->site_id);
                if ($site->getState() !== SiteState::ON) {
                    $site = null;
                }
            }
        }

        // If no site, fetch on the current site
        if (!$site) {
            if ($this->siteManager->hasContext()) {
                $site = $this->siteManager->getContext();
            }
        }

        if ($site) {
            $alias = $this->aliasManager->getPathAlias($node->id(), $site->getId());
        }

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
    public function getNodeSegment(NodeInterface $node) : ?string
    {
        if ($node->isNew()) {
            return null;
        }

        if ($this->isNodeBlacklisted($node)) {
            return null;
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
    public function normalizeSegment(string $value, int $maxLength = UCMS_SEO_SEGMENT_TRIM_LENGTH) : string
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
    public function setNodeSegment(NodeInterface $node, string $segment, ?string $previous = null) : void
    {
        if ($this->isNodeBlacklisted($node)) {
            return null;
        }

        if (!$previous) {
            $previous = $this->getNodeSegment($node);
        }
        if (empty($segment)) {
            $segment = null;
        }

        if ($previous === $segment) {
            return null; // Nothing to do
        }

        $this
            ->database
            ->merge('ucms_seo_node')
            ->key(['nid' => $node->id()])
            ->fields(['alias_segment' => $segment])
            ->execute()
        ;

        if (empty($segment)) {
            $this->onAliasChange([$node->id()]);
        } else {
            $this->onAliasChange([$node->id()]);
        }
    }

    /**
     * Set all impacted aliases as outdated
     *
     * @param array $nodeIdList
     */
    public function onAliasChange(array $nodeIdList) : void
    {
        $this->aliasManager->invalidateRelated($nodeIdList);
        $this->aliasCacheLookup->refresh();
    }

    /**
     * Set all impacted aliases as outdated
     *
     * @param int $menuId
     */
    public function onMenuChange(Menu $menu) : void
    {
        $this->aliasManager->invalidate(['site_id' => $menu->getSiteId()]);
        $this->aliasCacheLookup->refresh();
    }
}
