<?php

namespace MakinaCorpus\Ucms\Site;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

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
     * Find the most revelant ENABLED site to view the node in
     *
     * @param NodeInterface $node
     *
     * @see \MakinaCorpus\Ucms\Site\EventDispatcher\NodeEventSubscriber::onLoad()
     *
     * @return int
     *   The site identifier is returned, we don't need to load it to build
     *   a node route
     */
    public function findMostRelevantEnabledSiteFor(NodeInterface $node)
    {
        if (empty($node->ucms_enabled_sites)) {
            return; // Node cannot be viewed
        }

        if (in_array($node->site_id, $node->ucms_enabled_sites)) {
            // Per default, the primary site seems the best to work with
            return $node->site_id;
        }

        return reset($node->ucms_enabled_sites); // Fallback on first
    }

    /**
     * Find the most revelant site to view the node in
     *
     * @param NodeInterface $node
     * @param bool $onlyEnabled
     *   Search only in enabled sites
     *
     * @see \MakinaCorpus\Ucms\Site\EventDispatcher\NodeEventSubscriber::onLoad()
     *
     * @return int
     *   The site identifier is returned, we don't need to load it to build
     *   a node route
     */
    public function findMostRelevantSiteFor(NodeInterface $node)
    {
        if ($siteId = $this->findMostRelevantEnabledSiteFor($node)) {
            return $siteId;
        }
        if (empty($node->ucms_allowed_sites)) {
            return; // Node cannot be viewed
        }

        if (in_array($node->site_id, $node->ucms_allowed_sites)) {
            // Per default, the primary site seems the best to work with
            return $node->site_id;
        }

        return reset($node->ucms_allowed_sites); // Fallback on first
    }

    /**
     * Can the user publish (and unpublish) this node
     *
     * @param AccountInterface $account
     * @param NodeInterface $node
     *
     * @return boolean
     */
    public function userCanPublish(AccountInterface $account, NodeInterface $node)
    {
        if (!$node->access(Access::OP_VIEW, $account)) {
            return false; // Avoid breaking context (such as group)
        }
        if ($account->hasPermission(Access::PERM_CONTENT_GOD)) {
            return true;
        }
        if ($node->is_global && $account->hasPermission(Access::PERM_CONTENT_MANAGE_GLOBAL)) {
            return true;
        }
        if ($node->is_group && $account->hasPermission(Access::PERM_CONTENT_MANAGE_GROUP)) {
            return true;
        }
        if (!empty($node->site_id) && ($userSites = $this->manager->loadWebmasterSites($account))) {
            foreach ($userSites as $site) {
                if ($node->site_id == $site->id) {
                    return true;
                }
            }
        }
        return false;
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
        return $node->access(Access::OP_VIEW, $account) && $this->manager->getAccess()->userIsWebmaster($account);
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
        return $node->access(Access::OP_VIEW, $account) && in_array($site->getId(), $node->ucms_sites) && $this->manager->getAccess()->userIsWebmaster($account, $site);
    }

    /**
     * Can user promote or unpromote this node as a group node
     *
     * @param AccountInterface $account
     * @param NodeInterface $node
     *
     * @return boolean
     */
    public function userCanPromoteToGroup(AccountInterface $account, NodeInterface $node)
    {
        return $node->access(Access::OP_VIEW, $account) && ($node->is_group || $node->is_global) && $account->hasPermission(Access::PERM_CONTENT_MANAGE_GROUP);
    }

    /**
     * Can user view the given node
     *
     * @param AccountInterface $account
     * @param NodeInterface $node
     *
     * @return bool
     *
     * @deprecated
     */
    public function userCanView(AccountInterface $account, NodeInterface $node)
    {
        return node_access(Access::OP_VIEW, $node, $account);
    }

    /**
     * Can user edit the given node
     *
     * @param AccountInterface $account
     * @param NodeInterface $node
     *
     * @return bool
     *
     * @deprecated
     */
    public function userCanEdit(AccountInterface $account, NodeInterface $node)
    {
        return node_access(Access::OP_UPDATE, $node, $account);
    }

    /**
     * Can user delete the given node
     *
     * @param AccountInterface $account
     * @param NodeInterface $node
     *
     * @return bool
     *
     * @deprecated
     */
    public function userCanDelete(AccountInterface $account, NodeInterface $node)
    {
        return node_access(Access::OP_DELETE, $node, $account);
    }

    /**
     * Can user create nodes with the given type
     *
     * @param AccountInterface $account
     * @param string $type
     *
     * @return bool
     *
     * @deprecated
     */
    public function userCanCreate(AccountInterface $account, $type)
    {
        return node_access(Access::OP_CREATE, $type, $account);
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
        $request = \Drupal::request();
        if ($this->manager->hasContext()) {
            $previous = $this->manager->getContext();
            $this->manager->setContext($site, $request);
            $result = $this->userCanCreate($account, $type);
            $this->manager->setContext($previous, $request);
        } else {
            $this->manager->setContext($site, $request);
            $result = $this->userCanCreate($account, $type);
            $this->manager->dropContext();
        }
        return $result;
    }

    /**
     * Can user lock or unlock this node
     *
     * @param AccountInterface $account
     * @param NodeInterface $node
     *
     * @return boolean
     */
    public function userCanLock(AccountInterface $account, NodeInterface $node)
    {
        if (!$node->access(Access::OP_VIEW, $account)) {
            return false; // Avoid breaking context (such as group)
        }

        if ($node->is_group) {
            return $account->hasPermission(Access::PERM_CONTENT_MANAGE_GROUP);
        }

        if ($node->is_global) {
            return $account->hasPermission(Access::PERM_CONTENT_MANAGE_GLOBAL);
        }

        if ($node->site_id) {
            // Got a site !
            // @todo I must find a shortcut for this...
            return $this
                ->manager
                ->getAccess()
                ->userIsWebmaster(
                    $account,
                    $this
                        ->manager
                        ->getStorage()
                        ->findOne($node->site_id)
                )
            ;
        }

        return false;
    }

    /**
     * Can user copy this node
     *
     * @param AccountInterface $account
     * @param NodeInterface $node
     *
     * @return boolean
     */
    public function userCanDuplicate(AccountInterface $account, NodeInterface $node)
    {
        if (!$node->access(Access::OP_VIEW, $account)) {
            return false; // Avoid breaking context (such as group)
        }

        if (!$node->is_clonable) {
            return false;
        }
        if (empty($node->ucms_sites)) {
            return false;
        }

        $roles = $this->manager->getAccess()->getUserRoles($account);

        foreach (array_intersect_key($roles, array_flip($node->ucms_sites)) as $role) {
            if ($role->getRole() == Access::ROLE_WEBMASTER) {
                return true;
            }
        }

        return false;
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
