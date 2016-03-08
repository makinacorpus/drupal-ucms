<?php

namespace MakinaCorpus\Ucms\Site;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use MakinaCorpus\Ucms\Contrib\TypeHandler;

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
     * Grants for content owner in local repositories
     */
    const REALM_SITE_SELF = 'ucms_site_self';

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
    const REALM_GROUP_VIEW = 'ucms_group_view';

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
    public function __construct(SiteManager $manager, TypeHandler $typeHandler)
    {
        $this->manager = $manager;

        // Sorry for this, but we do need it to behave with Drupal internals
        $this->userGrantCache = &drupal_static('ucms_site_node_grants', []);
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
     * Alter-ego of hook_node_access_records().
     */
    public function getNodeGrants($node)
    {
        $ret = [];

        // This is where it gets complicated.
        $isGlobal   = $node->is_global;
        $isGroup    = $node->is_group;

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
                'realm'         => self::REALM_GROUP_VIEW,
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

        // Inject an entry for each site, even when the node is a global node, this
        // will tell the Drupal API system if the node is visible or not inside a
        // local site. Please note that we will never add the site state as a node
        // grant, this will be determined at runtime: the reason for this is that if
        // you change a site state, you would need to rebuild all its nodes grants
        // and this would not be tolerable.
        if (property_exists($node, 'ucms_sites') && !empty($node->ucms_sites)) {
            foreach ($node->ucms_sites as $siteId) {

                // Grant that reprensents the node in the site for anonymous
                // as long as it exists, not may show up anytime when the site
                // state is on
                $ret[] = [
                    'realm'         => self::REALM_PUBLIC,
                    'gid'           => $siteId,
                    'grant_view'    => $node->status,
                    'grant_update'  => 0,
                    'grant_delete'  => 0,
                    'priority'      => self::PRIORITY_DEFAULT,
                ];

                // This grand allows multiple business use cases:
                //   - user is a global administrator and can see everything
                //   - user is a contributor on a specific site
                //   - user is a webmaster on a readonly site
                $ret[] = [
                    'realm'         => self::REALM_READONLY,
                    'gid'           => $siteId,
                    'grant_view'    => 1,
                    'grant_update'  => 0,
                    'grant_delete'  => 0,
                    'priority'      => self::PRIORITY_DEFAULT,
                ];

                // Grant that reprensents the node in the site for webmasters
                $ret[] = [
                    'realm'         => self::REALM_WEBMASTER,
                    'gid'           => $siteId,
                    'grant_view'    => 1,
                    'grant_update'  => (int)(!$isGlobal && !$isGroup && $siteId === $node->site_id),
                    'grant_delete'  => (int)(!$isGlobal && !$isGroup && $siteId === $node->site_id),
                    'priority'      => self::PRIORITY_DEFAULT,
                ];
            }
        }

        return $ret;
    }

    /**
     * Alter-ego of hook_node_grants().
     */
    public function getUserGrants(AccountInterface $account, $op)
    {
        if (isset($this->userGrantCache[$account->uid][$op])) {
            return $this->userGrantCache[$account->uid][$op];
        }

        $ret  = [];
        $access = $this->manager->getAccess();
        $site   = $this->manager->getContext();

        if ($site) {

            if (SiteState::ON == $site->state) {
                // We are in a site context, user access rights must be tied to
                // this site, and everything global must be dropped from access
                // checks, thus ensuring sites isolation
                $ret[self::REALM_PUBLIC] = [$site->id];
            }

            // User can manager includes webmasters, so we're good to go
            if ($account->hasPermission(Access::PERM_CONTENT_VIEW_ALL)) {
                $ret[self::REALM_READONLY] = [$site->id];
            }

            if ($access->userCanView($account, $site)) {
                if ($access->userIsWebmaster($account, $site)) {
                    // Special case for archive, user might see but not edit
                    if (SiteState::ARCHIVE == $site->state) {
                        $ret[self::REALM_READONLY] = [$site->id];
                    } else {
                        $ret[self::REALM_WEBMASTER] = [$site->id];
                    }
                } else if ($access->userIsContributor($account, $site)) {
                    $ret[self::REALM_READONLY] = [$site->id];
                }
            }

        } else {

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
                    $ret[self::REALM_GROUP_VIEW] = [self::GID_DEFAULT];
                }
            }

            $grants = $this->manager->getAccess()->getUserRoles($account);

            // Preload all sites
            $siteIdList = [];
            foreach ($grants as $grant) {
                $siteIdList[] = $grant->getSiteId();
            }
            $sites = $this->manager->getStorage()->loadAll($siteIdList, false);

            foreach ($grants as $grant) {
                $siteId = $grant->getSiteId();
                if ($site = $sites[$siteId]) {
                    if ($access->userCanView($account, $site)) {
                        if (Access::ROLE_WEBMASTER == $grant->getRole()) {
                            if (SiteState::ARCHIVE === $site->state) {
                                $ret[self::REALM_READONLY][] = $siteId;
                            } else {
                                $ret[self::REALM_WEBMASTER][] = $siteId;
                            }
                        } else {
                            $ret[self::REALM_READONLY][] = $siteId;
                        }
                    }
                }
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
     * @return string
     */
    public function userCanAccess(AccountInterface $account, $node, $op)
    {
        $access = $this->manager->getAccess();

        if (Access::OP_CREATE === $op) {
            if (is_string($node) || $node instanceof NodeInterface) {

                $handler = $this->typeHandler;
                $type = is_string($node) ? $node : $node->bundle();

                $site = $this->manager->getContext();
                if ($site) {

                    // Prevent creating content on disabled or pending sites
                    if (!in_array($site->state, [SiteState::OFF, SiteState::ON])) {
                        return NODE_ACCESS_DENY;
                    }

                    // Contributor can only create editorial content
                    if ($access->userIsContributor($account, $site) && in_array($type, $handler->getEditorialTypes())) {
                        return NODE_ACCESS_ALLOW;
                    }

                    // Webmasters can create anything
                    if ($access->userIsWebmaster($account, $site) && in_array($type, $handler->getAllTypes())) {
                        return NODE_ACCESS_ALLOW;
                    }

                } else {
                    $canManage = $account->hasPermission(Access::PERM_CONTENT_MANAGE_GLOBAL)
                        || $account->hasPermission(Access::PERM_CONTENT_MANAGE_GROUP);
                    if ($canManage && in_array($type, $handler->getEditorialTypes())) {
                        return NODE_ACCESS_ALLOW;
                    }
                }
            }

            return NODE_ACCESS_DENY;
        }

        $grants   = $this->getUserGrants($account, $op);
        $records  = $this->getNodeGrants($node);
        $prop     = 'grant_' . $op;

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

        return NODE_ACCESS_DENY;
    }

    /**
     * Can the user reference this node on one of his sites
     *
     * @param NodeInterface $node
     * @param AccountInterface $account
     *
     * @return boolean
     */
    public function userCanReference(AccountInterface $account, NodeInterface $node)
    {
        // Let's say, from this very moment, that as long as the user can see
        // the node he might want to add it on one of his sites
        return true;
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
        return ($node->is_group || $node->is_group) && $account->hasPermission(Access::PERM_CONTENT_MANAGE_GROUP);
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
                        ->findOne($node->site_id),
                    $account
                )
            ;
        }

        return false;
    }
}
