<?php

namespace MakinaCorpus\Ucms\Site;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use MakinaCorpus\ACL\Permission;
use Symfony\Component\HttpFoundation\Request;

/**
 * Drupal ACL builder for usage with node_access() related hooks
 */
final class NodeAccessService
{
    private $manager;

    /**
     * Default constructor
     *
     * @param SiteManager $manager
     */
    public function __construct(SiteManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Find the most revelant site to view the node in
     *
     * @param NodeInterface $node
     *
     * @see MakinaCorpus\Ucms\Site\EventDispatcher\NodeEventSubscriber::onLoad()
     *
     * @return int
     *   The site identifier is returned, we don't need to load it to build
     *   a node route
     */
    public function findMostRelevantSiteFor(NodeInterface $node)
    {
        if (empty($node->ucms_allowed_sites)) {
            return; // Node cannot be viewed
        }

        if (in_array($node->site_id, $node->ucms_allowed_sites)) {
            // Per default, the primary site seems the best to work with
            return $node->site_id;
        }

        // First one seems the best one.
        return reset($node->ucms_allowed_sites);
    }

    /**
     * Can the user reference this node on one of his sites
     *
     * @param AccountInterface $account
     * @param NodeInterface $node
     *
     * @return boolean
     */
    public function userCanReference(AccountInterface $account, NodeInterface $node)
    {
        return $node->access(Permission::VIEW, $account) && $this->manager->getAccess()->userIsWebmaster($account);
    }

    /**
     * Can the user dereference the current content from the given site
     *
     * @param AccountInterface $account
     * @param NodeInterface $node
     * @param Site $site
     *
     * @return boolean
     */
    public function userCanDereference(AccountInterface $account, NodeInterface $node, Site $site)
    {
        return $node->access(Permission::VIEW, $account) && in_array($site->getId(), $node->ucms_sites) && $this->manager->getAccess()->userIsWebmaster($account, $site);
    }

    /**
     * Can user create nodes with the given type
     *
     * @param AccountInterface $account
     * @param string $type
     *
     * @return bool
     */
    public function userCanCreate(AccountInterface $account, $type)
    {
        return node_access('create', $type, $account);
    }

    /**
     * Can user create nodes with the given type in the given site context
     *
     * @param AccountInterface $account
     * @param string $type
     * @param Site $site
     *
     * @return bool
     *
     * @deprecated
     */
    public function userCanCreateInSite(AccountInterface $account, $type, Site $site)
    {
        // Damn this is ugly
        if ($this->manager->hasContext()) {
            $previous = $this->manager->getContext();
            $this->manager->setContext($site, new Request());
            $result = $this->userCanCreate($account, $type);
            $this->manager->setContext($previous, new Request());
        } else {
            $this->manager->setContext($site, new Request());
            $result = $this->userCanCreate($account, $type);
            $this->manager->dropContext();
        }
        return $result;
    }

    /**
     * Can user create type in our platform
     *
     * @param \Drupal\Core\Session\AccountInterface $account
     * @param string $type
     * @return bool
     */
    public function userCanCreateInAnySite(AccountInterface $account, $type)
    {
        // Check for global contribs
        if ($this->userCanCreate($account, $type)) {
            return true;
        }

        // Iterate over all sites, check if type creation is possible in context
        $sites = $this->manager->loadOwnSites($account);
        foreach ($sites as $site) {
            if ($this->userCanCreateInSite($account, $type, $site)) {
                return true;
            }
        }

        return false;
    }
}
