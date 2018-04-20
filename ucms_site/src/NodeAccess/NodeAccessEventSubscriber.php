<?php

namespace MakinaCorpus\Ucms\Site\NodeAccess;

use Drupal\Core\Session\AccountInterface;
use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeAccessEvent;
use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeAccessGrantEvent;
use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeAccessRecordEvent;
use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeAccessSubscriber as NodeAccessCache;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvents;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteInitEvent;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Uses the abstraction provided by the sf_dic module to collect node access
 * grants and user grants, so benefit from the generic method it provides
 * to intersect those at runtime.
 */
final class NodeAccessEventSubscriber implements EventSubscriberInterface
{
    /**
     * Grants for anonymous users
     */
    const REALM_PUBLIC = 'ucms_public';

    /**
     * This is for technical administrators only
     */
    const REALM_GOD = 'god_mode';

    /**
     * Grants for local webmasters
     */
    const REALM_SITE_WEBMASTER = 'ucms_site';

    /**
     * Grants for site members that cannot edit content
     */
    const REALM_SITE_READONLY = 'ucms_site_ro';

    /**
     * Grants for local contributors
     */
    const REALM_READONLY = 'ucms_site_all_ro';

    /**
     * Grants for other sites
     */
    const REALM_OTHER = 'ucms_site_other';

    /**
     * Grants for people accessing the dashboard
     */
    const REALM_GLOBAL_READONLY = 'ucms_global_ro';

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
    private $siteManager;

    /**
     * @var NodeAccessCache
     */
    private $nodeAccessCache;

    /**
     * Default constructor
     *
     * @param SiteManager $manager
     */
    public function __construct(SiteManager $siteManager, NodeAccessCache $nodeAccessCache)
    {
        $this->siteManager = $siteManager;
        $this->nodeAccessCache = $nodeAccessCache;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            NodeAccessEvent::EVENT_NODE_ACCESS => [
                ['onNodeAccess', 128],
            ],
            NodeAccessRecordEvent::EVENT_NODE_ACCESS_RECORD => [
                ['onNodeAccessRecord', 128],
            ],
            NodeAccessGrantEvent::EVENT_NODE_ACCESS_GRANT => [
                ['onNodeAccessGrant', 128],
            ],
            SiteEvents::EVENT_INIT => [
                ['onSiteInit', 0],
            ],
            SiteEvents::EVENT_DROP => [
                ['onSiteDrop', 0],
            ],
        ];
    }

    /**
     * Reset node access caches
     */
    private function resetCache()
    {
        drupal_static_reset('node_access');
        drupal_static_reset('user_access');

        $this->nodeAccessCache->resetCache();
    }

    /**
     * On site init clear node access caches
     */
    public function onSiteInit(SiteInitEvent $event)
    {
        $this->resetCache();
    }

    /**
     * On site drop clear node access caches
     */
    public function onSiteDrop(SiteEvent $event)
    {
        $this->resetCache();
    }

    /**
     * Collect node grants event listener
     */
    public function onNodeAccessRecord(NodeAccessRecordEvent $event)
    {
        $node = $event->getNode();

        // This is where it gets complicated.
        $isGlobal     = $node->is_global;
        $isGroup      = $node->is_group;
        $isNotLocal   = $isGlobal || $isGroup;
        $isPublished  = $node->isPublished();

        // People with "view all" permissions should view it
        $event->add(self::REALM_READONLY, self::GID_DEFAULT);
        $event->add(self::REALM_GOD, self::GID_DEFAULT);

        // This handles two grants in one:
        //  - Webmasters can browse along published content of other sites
        //  - People with global repository access may see this content

        if ($isGroup) {
            $event->add(self::REALM_GROUP, self::GID_DEFAULT, true, true, true);
            if ($isPublished) { // Avoid data volume exploding
                $event->add(self::REALM_GROUP_READONLY, self::GID_DEFAULT);
            }
        } else if ($isGlobal) {
            $event->add(self::REALM_GLOBAL, self::GID_DEFAULT, true, true, true);
            if ($isPublished) { // Avoid data volume exploding
                $event->add(self::REALM_GLOBAL_READONLY, self::GID_DEFAULT);
            }
        }

        // This allows other webmasters to see other site content, but please
        // beware that it drops out the site's state from the equation, there
        // is no easy way of doing this except by rewriting all site content
        // node access rights on each site status change, and that's sadly a
        // no-go.
        if (!$isNotLocal && $isPublished) {
            $event->add(self::REALM_OTHER, self::GID_DEFAULT);
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
                if ($isPublished) {
                    $event->add(self::REALM_PUBLIC, $siteId);
                }

                // This grand allows multiple business use cases:
                //   - user is a global administrator and can see everything
                //   - user is a contributor on a specific site
                //   - user is a webmaster on a readonly site
                if ($isNotLocal) {
                    if ($isPublished) {
                        $event->add(self::REALM_SITE_READONLY, $siteId);
                        $event->add(self::REALM_SITE_WEBMASTER, $siteId);
                    }
                } else  {
                    $event->add(self::REALM_SITE_READONLY, $siteId);
                    if ($siteId === $node->site_id) { // Avoid data volume exploding
                        $event->add(self::REALM_SITE_WEBMASTER, $siteId, true, true, true);
                    }
                }
            }
        }
    }

    private function buildNodeAccessGrant(AccountInterface $account, $op)
    {
        $ret = [];

        // This should always be true anyway.
        if (($site = $this->siteManager->getContext()) && SiteState::ON === $site->getState()) {
            $ret[self::REALM_PUBLIC][] = $site->getId();
        }

        // Shortcut for anonymous users, or users with no specific roles
        if ($account->isAnonymous()) {
            return $ret;
        }

        // God mode.
        if ($account->hasPermission(Access::PERM_CONTENT_GOD)) {
            $ret[self::REALM_GOD][] = self::GID_DEFAULT;
            return $ret;
        }

        if ($account->hasPermission(Access::PERM_CONTENT_MANAGE_GLOBAL)) {
            $ret[self::REALM_GLOBAL][] = self::GID_DEFAULT;
        }
        if ($account->hasPermission(Access::PERM_CONTENT_MANAGE_GROUP)) {
            $ret[self::REALM_GROUP][] = self::GID_DEFAULT;
        }

        if ($account->hasPermission(Access::PERM_CONTENT_VIEW_ALL)) {
            $ret[self::REALM_READONLY][] = self::GID_DEFAULT;
        } else {
            if ($account->hasPermission(Access::PERM_CONTENT_VIEW_GLOBAL) || $account->hasPermission(Access::PERM_CONTENT_MANAGE_GLOBAL)) {
                $ret[self::REALM_GLOBAL_READONLY][] = self::GID_DEFAULT;
            }
            if ($account->hasPermission(Access::PERM_CONTENT_VIEW_GROUP) || $account->hasPermission(Access::PERM_CONTENT_MANAGE_GROUP)) {
                $ret[self::REALM_GROUP_READONLY][] = self::GID_DEFAULT;
            }
            if ($account->hasPermission(Access::PERM_CONTENT_VIEW_OTHER)) {
                $ret[self::REALM_OTHER][] = self::GID_DEFAULT;
            }
        }

        $grants = $this->siteManager->getAccess()->getUserRoles($account);

        foreach ($grants as $grant) {
            $siteId = $grant->getSiteId();

            if (Access::ROLE_WEBMASTER === $grant->getRole()) {
                switch ($grant->getSiteState()) {

                    case SiteState::ON:
                    case SiteState::OFF:
                    case SiteState::INIT:
                        $ret[self::REALM_SITE_WEBMASTER][] = $siteId;
                        // It is required to set the readonly realm as well since there
                        // might be content referenced in site, but not belonging to it,
                        // which means they would then be invisibile to the webmasters.
                        $ret[self::REALM_SITE_READONLY][] = $siteId;
                        break;

                    case SiteState::ARCHIVE:
                        $ret[self::REALM_SITE_READONLY][] = $siteId;
                        break;
                }
            } else {
                switch ($grant->getSiteState()) {

                    case SiteState::ON:
                    case SiteState::OFF:
                        $ret[self::REALM_SITE_READONLY][] = $siteId;
                        break;
                }
            }
        }

        return $ret;
    }

    /**
     * Collect user grants method
     */
    public function onNodeAccessGrant(NodeAccessGrantEvent $event)
    {
        $ret = $this->buildNodeAccessGrant($event->getAccount(), $event->getOperation());

        foreach ($ret as $realm => $gids) {
            foreach ($gids as $gid) {
                $event->add($realm, $gid);
            }
        }
    }

    /**
     * Check node access event listener
     *
     * @see \MakinaCorpus\Ucms\Contrib\NodeAccess\NodeAccessEventSubscriber
     *   Important note: if you are looking for CREATION ACCESS, please look
     *   into the 'ucms_contrib' module, which drives creation access rights
     *   using the type handler.
     */
    public function onNodeAccess(NodeAccessEvent $event)
    {
        $node     = $event->getNode();
        $account  = $event->getAccount();
        $op       = $event->getOperation();
        $access   = $this->siteManager->getAccess();

        if (Access::OP_CREATE === $op) {

            if ($this->siteManager->hasContext()) {
                $site = $this->siteManager->getContext();

                // Prevent creating content on disabled or pending sites
                if (!in_array($site->getState(), [SiteState::INIT, SiteState::OFF, SiteState::ON])) {
                    return $event->deny();
                }
            }

            // All other use cases will be driven by other modules; depending
            // on the user role (webmaser, admin, contributor, or any other)
            // the content creation will be authorized or denied by Drupal core
            // permissions
            return $event->ignore();
        }

        // For some reasons, and because we don't care about the 'update'
        // operation in listings, we are going to hardcode a few behaviors
        // in this method, which won't affect various listings
        if ('update' === $op && $account->uid && $node->uid == $account->uid) {
            if ($node->ucms_sites) {
                // Site contributors can update their own content in sites
                foreach ($access->getUserRoles($account) as $grant) {
                    if (in_array($grant->getSiteId(), $node->ucms_sites)) {
                        return $event->allow();
                    }
                }
            }
        }

        return $event->ignore();
    }
}
