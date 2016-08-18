<?php

namespace MakinaCorpus\Ucms\Site;

use MakinaCorpus\Ucms\Contrib\TypeHandler;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Drupal ACL builder for usage with node_access() related hooks
 */
class NodeAccessService
{
    /**
     * Grants for anonymous users
     */
    const REALM_PUBLIC = 'ucms_public';

    /**
     * Grants for local webmasters
     */
    const REALM_WEBMASTER = 'ucms_site';

    /**
     * Grants for local contributors
     */
    const REALM_READONLY = 'ucms_site_ro';

    /**
     * Grants for other sites
     */
    const REALM_OTHER = 'ucms_site_other';

    /**
     * Grants for people accessing the dashboard
     */
    const REALM_GLOBAL_VIEW = 'ucms_global_view';

    /**
     * Grants for global content
     */
    const REALM_GLOBAL = 'ucms_global';

    /**
     * Grants for group content
     */
    const REALM_GROUP_READONLY = 'ucms_group_ro';

    /**
     * Grants for group content
     */
    const REALM_GROUP = 'ucms_group';

    /**
     * Grants for content owner in global repository
     */
    const REALM_GLOBAL_SELF = 'ucms_global_self';

    /**
     * Default group identifier for grants where it does not make sense
     */
    const GID_DEFAULT = 0;

    /**
     * Default priority for grants
     */
    const PRIORITY_DEFAULT = 1;

    /**
     * @var SiteManager
     */
    private $manager;

    /**
     * @var mixed[]
     */
    private $userGrantCache;
    /**
     * @var TypeHandler
     */
    private $typeHandler;

    /**
     * Default constructor
     *
     * @param SiteManager $manager
     * @param TypeHandler $typeHandler
     */
    public function __construct(SiteManager $manager, TypeHandler $typeHandler = null)
    {
        $this->manager = $manager;

        // Sorry for this, but we do need it to behave with Drupal internals
        $this->userGrantCache = &drupal_static('ucms_site_node_grants', []);
        $this->typeHandler = $typeHandler;
    }

    /**
     * @param TypeHandler $typeHandler
     */
    public function setTypeHandler($typeHandler)
    {
        $this->typeHandler = $typeHandler;
    }

    /**
     * Reset internal cache
     */
    public function resetCache()
    {
        drupal_static_reset('ucms_site_node_grants');

        $this->userGrantCache = &drupal_static('ucms_site_node_grants', []);

        $this->manager->getAccess()->resetCache();
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
    public function findMostRevelantSiteFor(NodeInterface $node)
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
     * Alter-ego of hook_node_access_records().
     */
    public function getNodeGrants($node)
    {
        $ret = [];

        // This is where it gets complicated.
        $isGlobal   = $node->is_global;
        $isGroup    = $node->is_group;
        $isNotLocal = $isGlobal || $isGroup;

        // People with "view all" permissions should view it
        $ret[] = [
            'realm'         => self::REALM_READONLY,
            'gid'           => self::GID_DEFAULT,
            'grant_view'    => 1,
            'grant_update'  => 0,
            'grant_delete'  => 0,
            'priority'      => self::PRIORITY_DEFAULT,
        ];

        // This handles two grants in one:
        //  - Webmasters can browse along published content of other sites
        //  - People with global repository access may see this content

        if ($isGroup) {
            $ret[] = [
                'realm'         => self::REALM_GROUP,
                'gid'           => self::GID_DEFAULT,
                'grant_view'    => 1,
                'grant_update'  => 1,
                'grant_delete'  => 1,
                'priority'      => self::PRIORITY_DEFAULT,
            ];
            $ret[] = [
                'realm'         => self::REALM_GROUP_READONLY,
                'gid'           => self::GID_DEFAULT,
                'grant_view'    => $node->status,
                'grant_update'  => 0,
                'grant_delete'  => 0,
                'priority'      => self::PRIORITY_DEFAULT,
            ];
        } else if ($isGlobal) {
            $ret[] = [
                'realm'         => self::REALM_GLOBAL,
                'gid'           => self::GID_DEFAULT,
                'grant_view'    => 1,
                'grant_update'  => 1,
                'grant_delete'  => 1,
                'priority'      => self::PRIORITY_DEFAULT,
            ];
            $ret[] = [
                'realm'         => self::REALM_GLOBAL_VIEW,
                'gid'           => self::GID_DEFAULT,
                'grant_view'    => $node->status,
                'grant_update'  => 0,
                'grant_delete'  => 0,
                'priority'      => self::PRIORITY_DEFAULT,
            ];
        }

        // This allows other webmasters to see other site content, but please
        // beware that it drops out the site's state from the equation, there
        // is no easy way of doing this except by rewriting all site content
        // node access rights on each site status change, and that's sadly a
        // no-go.
        if (!$isNotLocal) {
            if ($node->status) {
                $ret[] = [
                    'realm'         => self::REALM_OTHER,
                    'gid'           => self::GID_DEFAULT,
                    'grant_view'    => 1,
                    'grant_update'  => 0,
                    'grant_delete'  => 0,
                    'priority'      => self::PRIORITY_DEFAULT,
                ];
            }
        }

        // Inject an entry for each site, even when the node is a global node, this
        // will tell the Drupal API system if the node is visible or not inside a
        // local site. Please note that we will never add the site state as a node
        // grant, this will be determined at runtime: the reason for this is that if
        // you change a site state, you would need to rebuild all its nodes grants
        // and this would not be tolerable.
        if (property_exists($node, 'ucms_sites') && !empty($node->ucms_sites)) {
            foreach (array_unique($node->ucms_sites) as $siteId) {

                // Grant that reprensents the node in the site for anonymous
                // as long as it exists, not may show up anytime when the site
                // state is on
                if ($node->status) {
                    $ret[] = [
                        'realm'         => self::REALM_PUBLIC,
                        'gid'           => $siteId,
                        'grant_view'    => 1,
                        'grant_update'  => 0,
                        'grant_delete'  => 0,
                        'priority'      => self::PRIORITY_DEFAULT,
                    ];
                }

                // This grand allows multiple business use cases:
                //   - user is a global administrator and can see everything
                //   - user is a contributor on a specific site
                //   - user is a webmaster on a readonly site
                if ($isNotLocal) {
                    if ($node->status) {
                        $ret[] = [
                            'realm'         => self::REALM_READONLY,
                            'gid'           => $siteId,
                            'grant_view'    => 1,
                            'grant_update'  => 0,
                            'grant_delete'  => 0,
                            'priority'      => self::PRIORITY_DEFAULT,
                        ];
                        $ret[] = [
                            'realm'         => self::REALM_WEBMASTER,
                            'gid'           => $siteId,
                            'grant_view'    => 1,
                            'grant_update'  => 0,
                            'grant_delete'  => 0,
                            'priority'      => self::PRIORITY_DEFAULT,
                        ];
                    }
                } else  {
                    $ret[] = [
                        'realm'         => self::REALM_READONLY,
                        'gid'           => $siteId,
                        'grant_view'    => 1,
                        'grant_update'  => 0,
                        'grant_delete'  => 0,
                        'priority'      => self::PRIORITY_DEFAULT,
                    ];
                    $ret[] = [
                        'realm'         => self::REALM_WEBMASTER,
                        'gid'           => $siteId,
                        'grant_view'    => 1,
                        'grant_update'  => $siteId === $node->site_id ? 1 : 0,
                        'grant_delete'  => $siteId === $node->site_id ? 1 : 0,
                        'priority'      => self::PRIORITY_DEFAULT,
                    ];
                }
            }
        }

        return $ret;
    }

    /**
     * Alter-ego of hook_node_grants().
     */
    public function getUserGrants(AccountInterface $account, $op)
    {
        $userId = $account->id();

        if (isset($this->userGrantCache[$userId][$op])) {
            return $this->userGrantCache[$userId][$op];
        }

        $ret = [];

        // This should always be true anyway.
        if (
            ($site = $this->manager->getContext()) &&
            (SiteState::ON === $site->state) &&
            (true === $site->isPublic())
        ) {
            $ret[self::REALM_PUBLIC] = [$site->getId()];
        }

        // Shortcut for anonymous users, or users with no specific roles
        if ($account->isAnonymous()) {
            return $ret;
        }

        if ($account->hasPermission(Access::PERM_CONTENT_MANAGE_GLOBAL)) {
            $ret[self::REALM_GLOBAL] = [self::GID_DEFAULT];
        }
        if ($account->hasPermission(Access::PERM_CONTENT_MANAGE_GROUP)) {
            $ret[self::REALM_GROUP] = [self::GID_DEFAULT];
        }

        if ($account->hasPermission(Access::PERM_CONTENT_VIEW_ALL)) {
            $ret[self::REALM_READONLY] = [self::GID_DEFAULT];
        } else {
            if ($account->hasPermission(Access::PERM_CONTENT_VIEW_GLOBAL)) {
                $ret[self::REALM_GLOBAL_VIEW] = [self::GID_DEFAULT];
            }
            if ($account->hasPermission(Access::PERM_CONTENT_VIEW_GROUP)) {
                $ret[self::REALM_GROUP_READONLY] = [self::GID_DEFAULT];
            }
            if ($account->hasPermission(Access::PERM_CONTENT_VIEW_OTHER)) {
                $ret[self::REALM_OTHER] = [self::GID_DEFAULT];
            }
        }

        $grants = $this->manager->getAccess()->getUserRoles($account);

        foreach ($grants as $grant) {
            $siteId = $grant->getSiteId();
            if (Access::ROLE_WEBMASTER == $grant->getRole()) {
                switch ($grant->getSiteState()) {
                    case SiteState::ON:
                    case SiteState::OFF:
                    case SiteState::INIT:
                        $ret[self::REALM_WEBMASTER][] = $siteId;
                        break;
                    case SiteState::ARCHIVE:
                        $ret[self::REALM_READONLY][] = $siteId;
                        break;
                }
            }
            elseif (Access::ROLE_CONTRIB == $grant->getRole()) {
                switch ($grant->getSiteState()) {
                    case SiteState::ON:
                    case SiteState::OFF:
                        $ret[self::REALM_READONLY][] = $siteId;
                        break;
                }
            }
            elseif (SiteState::ON == $grant->getSiteState()) {
                $ret[self::REALM_READONLY][] = $siteId;
            }
        }

        return $this->userGrantCache[$account->uid][$op] = $ret;
    }

    /**
     * Alter-ego of hook_node_access().
     *
     * @param AccountInterface $account
     * @param NodeInterface|string $node
     * @param string $op
     * @param Site $site
     * @return string
     */
    public function userCanAccess(AccountInterface $account, $node, $op, Site $site = null)
    {
        $access = $this->manager->getAccess();

        if (!$site && $this->manager->hasContext()) {
            $site = $this->manager->getContext();
        }

        if (Access::OP_CREATE === $op) {
            if (is_string($node) || $node instanceof NodeInterface) {

                $handler = $this->typeHandler;
                $type = is_string($node) ? $node : $node->bundle();

                // Locked types
                if (in_array($type, $this->typeHandler->getLockedTypes()) && !$account->hasPermission(Access::PERM_CONTENT_MANAGE_ALL)) {
                    return NODE_ACCESS_DENY;
                }

                if ($site) {

                    // Prevent creating content on disabled or pending sites
                    if (!in_array($site->state, [SiteState::INIT, SiteState::OFF, SiteState::ON])) {
                        return NODE_ACCESS_DENY;
                    }

                    if ($this->typeHandler) {

                        // Contributor can only create editorial content
                        if ($access->userIsContributor($account, $site) && in_array($type, $handler->getEditorialTypes())) {
                            return NODE_ACCESS_ALLOW;
                        }

                        // Webmasters can create anything
                        if ($access->userIsWebmaster($account, $site) && in_array($type, $handler->getAllTypes())) {
                            return NODE_ACCESS_ALLOW;
                        }
                    }

                } else {
                    $canManage = $account->hasPermission(Access::PERM_CONTENT_MANAGE_GLOBAL)
                        || $account->hasPermission(Access::PERM_CONTENT_MANAGE_GROUP);
                    if ($canManage && in_array($type, $handler->getEditorialTypes())) {
                        return NODE_ACCESS_ALLOW;
                    }
                }
            }

            return NODE_ACCESS_IGNORE;

        } elseif (Access::OP_DELETE === $op) {
            // Locked types
            if (in_array($node->bundle(), $this->typeHandler->getLockedTypes()) && !$account->hasPermission(Access::PERM_CONTENT_MANAGE_ALL)) {
                return NODE_ACCESS_DENY;
            }
        }

        $grants = $this->getUserGrants($account, $op);

        // Simple shortcut, if you have no roles, just get out.
        if (empty($grants)) {
            return NODE_ACCESS_IGNORE;
        }

        $records = $this->getNodeGrants($node);
        $prop = 'grant_' . $op;

        foreach ($records as $record) {

            if (empty($record[$prop])) {
                continue;
            }

            foreach ($grants as $realm => $gids) {
                if ($realm === $record['realm'] && in_array($record['gid'], $gids)) {
                    return NODE_ACCESS_ALLOW;
                }
            }
        }

        // For some reasons, and because we don't care about the 'update'
        // operation in listings, we are going to hardcode a few behaviors
        // in this method, which won't affect various listings
        if ('update' === $op && $account->uid && $node->uid == $account->uid) {
            if ($node->ucms_sites) {
                // Site contributors can update their own content in sites
                foreach ($access->getUserRoles($account) as $grant) {
                    if (in_array($grant->getSiteId(), $node->ucms_sites)) {
                        return NODE_ACCESS_ALLOW;
                    }
                }
            }
        }

        return NODE_ACCESS_IGNORE;
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
        return ($node->is_group || $node->is_global) && $account->hasPermission(Access::PERM_CONTENT_MANAGE_GROUP);
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
        if ($this->userCanAccess($account, $type, Access::OP_CREATE) !== NODE_ACCESS_DENY) {
            return true;
        }

        // Iterate over all sites, check if type creation is possible in context
        $sites = $this->manager->loadOwnSites($account);
        foreach ($sites as $site) {
            if ($this->userCanAccess($account, $type, Access::OP_CREATE, $site) !== NODE_ACCESS_DENY) {
                return true;
            }
        }

        return false;
    }
}
