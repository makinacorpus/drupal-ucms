<?php

namespace MakinaCorpus\Ucms\Site;

use MakinaCorpus\Ucms\Site\Access;

/**
 * Drupal ACL builder for usage with node_access() related hooks
 */
class NodeAccessHelper
{
    /**
     * Grants for anonymous users
     */
    const REALM_PUBLIC = 'ucms_public';

    /**
     * Grants for webmasters
     */
    const REALM_WEBMASTER = 'ucms_site';

    /**
     * Grants for people accessing the dashboard
     */
    const REALM_GLOBAL_VIEW = 'ucms_global_view';

    /**
     * Grants for global content
     */
    const REALM_GLOBAL = 'ucms_global';

    /**
     * Grants for global non editable locked content
     */
    const REALM_GLOBAL_LOCKED = 'ucms_global_locked';

    /**
     * Grants for super viewers
     */
    const REALM_ALL = 'ucms_all';

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
     * Default constructor
     *
     * @param SiteManager $manager
     */
    public function __construct(SiteManager $manager)
    {
        $this->manager = $manager;

        // Sorry for this, but we do need it to behave with Drupal internals
        $this->userGrantCache = &drupal_static('ucms_site_node_grants', []);
    }

    /**
     * Reset internal cache
     */
    public function resetCache()
    {
        drupal_static_reset('ucms_site_node_grants');
    }

    /**
     * Alter-ego of hook_node_access_records().
     */
    public function getNodeGrants($node)
    {
        $ret = [];

        // This is where it gets complicated.
        $isGlobal   = $node->is_global;
        $isClonable = $node->is_clonable;

        // People with "view all" permissions should view it
        $ret[] = [
            'realm'         => self::REALM_ALL,
            'gid'           => self::GID_DEFAULT,
            'grant_view'    => 1,
            'grant_update'  => 0,
            'grant_delete'  => 0,
            'priority'      => self::PRIORITY_DEFAULT,
        ];

        // This handles two grants in one:
        //  - Webmasters can browse along published content of other sites
        //  - People with global repository access may see this content
        $ret[] = [
            'realm'         => self::REALM_GLOBAL_VIEW,
            'gid'           => self::GID_DEFAULT,
            'grant_view'    => $node->status,
            'grant_update'  => 0,
            'grant_delete'  => 0,
            'priority'      => self::PRIORITY_DEFAULT,
        ];

        if ($isGlobal) {

            $ret[] = [
                'realm'         => self::REALM_GLOBAL_LOCKED,
                'gid'           => self::GID_DEFAULT,
                'grant_view'    => 1,
                'grant_update'  => 1,
                'grant_delete'  => 1,
                'priority'      => self::PRIORITY_DEFAULT,
            ];

            $ret[] = [
                'realm'         => self::REALM_GLOBAL,
                'gid'           => self::GID_DEFAULT,
                'grant_view'    => 1,
                'grant_update'  => (int)$isClonable,
                'grant_delete'  => (int)$isClonable,
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

                // People with "view all" permissions should view it
                // This is necessary to duplicate it even thought it already
                // exists upper, because we changed the gid to ensure isolation
                // at runtime, when in site context
                $ret[] = [
                    'realm'         => self::REALM_ALL,
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
                    'grant_update'  => (int)(!$isGlobal && $siteId === $node->site_id),
                    'grant_delete'  => (int)(!$isGlobal && $siteId === $node->site_id),
                    'priority'      => self::PRIORITY_DEFAULT,
                ];
            }
        }

        return $ret;
    }

    /**
     * Alter-ego of hook_node_grants().
     */
    public function getUserGrants($account, $op)
    {
        if (isset($this->userGrantCache[$account->uid][$op])) {
            return $this->userGrantCache[$account->uid][$op];
        }

        $ret  = [];
        $userId = $account->uid;
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
            if (user_access(Access::PERM_CONTENT_VIEW_ALL, $account)) {
                $ret[self::REALM_ALL] = [$site->id];
            }
            if ($access->userCanView($site, $userId) && $access->userIsWebmaster($site, $userId)) {
                // Special case for archive, user might see but not edit
                if (SiteState::ARCHIVE == $site->state) {
                    $ret[self::REALM_ALL] = [$site->id];
                } else {
                    $ret[self::REALM_WEBMASTER] = [$site->id];
                }
            }

        } else {

            if (user_access(Access::PERM_CONTENT_MANAGE_GLOBAL, $account)) {
                $ret[self::REALM_GLOBAL] = [self::GID_DEFAULT];
            }
            if (user_access(Access::PERM_CONTENT_MANAGE_GLOBAL_LOCKED, $account)) {
                $ret[self::REALM_GLOBAL_LOCKED] = [self::GID_DEFAULT];
            }
            if (user_access(Access::PERM_CONTENT_VIEW_ALL, $account)) {
                $ret[self::REALM_ALL] = [self::GID_DEFAULT];
            } else if (user_access(Access::PERM_CONTENT_VIEW_GLOBAL, $account)) {
                $ret[self::REALM_GLOBAL_VIEW] = [self::GID_DEFAULT];
            }

            if (false) {
                // @todo
                //   - List of sites the webmaster is webmaster of, this
                //     probably will be a performance killer though...
                //   - Oh and those sites might only be init, on, off or archive
                $ret[self::REALM_WEBMASTER] = [];
            }
        }

        return $this->userGrantCache[$userId][$op] = $ret;
    }

    /**
     * Alter-ego of hook_node_access().
     */
    public function canUserAccess($node, $op, $account)
    {
        if (is_string($node)) {
            if ('create' === $op) {
                // @todo Check creation permissions;
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

        return NODE_ACCESS_DENY;
    }
}
