<?php

namespace MakinaCorpus\Ucms\Site\ACL;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use MakinaCorpus\ACL\Permission;
use MakinaCorpus\ACL\Collector\EntryCollectorInterface;
use MakinaCorpus\ACL\Collector\EntryListBuilderInterface;
use MakinaCorpus\ACL\Collector\ProfileCollectorInterface;
use MakinaCorpus\ACL\Collector\ProfileSetBuilder;
use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeAccessEvent;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvents;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteInitEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class NodeEntryCollector implements EntryCollectorInterface, ProfileCollectorInterface, EventSubscriberInterface
{
    /**
     * Supported permissions
     */
    private static $supportedPermissions = [
        Access::ACL_PERM_CONTENT_PROMOTE_CORPORATE => true,
        Access::ACL_PERM_SITE_EDIT_TREE => true,
        Access::ACL_PERM_MANAGE_USERS => true,
        Permission::DELETE => true,
        Permission::LOCK => true,
        Permission::PUBLISH => true,
        Permission::UPDATE => true,
        Permission::VIEW => true,
    ];

    private $entityManager;
    private $siteManager;

    /**
     * Default constructor
     */
    public function __construct(EntityManager $entityManager, SiteManager $siteManager)
    {
        $this->entityManager = $entityManager;
        $this->siteManager = $siteManager;

        // Easy and ulgy way (@todo fix me, do this at compile time)
        /** @var \MakinaCorpus\ACL\PermissionMap $permissionMap */
        $permissionMap = \Drupal::service('acl.permission_map');
        // Avoid duplicate definition during unit tests.
        if (!$permissionMap->supports(Access::ACL_PERM_CONTENT_PROMOTE_CORPORATE)) {
            \Drupal::service('acl.permission_map')->addPermissions([
                Access::ACL_PERM_CONTENT_PROMOTE_CORPORATE => 32768,
                Access::ACL_PERM_SITE_EDIT_TREE => 65536,
                Access::ACL_PERM_MANAGE_USERS => 131072,
                Access::ACL_PERM_MANAGE_SITES => 262144,
            ]);
        }
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
     * {@inheritdoc}
     */
    public function supports($type, $permission)
    {
        return 'node' === $type && isset(self::$supportedPermissions[$permission]);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsType($type)
    {
        return 'node' === $type;
    }

    /**
     * Collect entries for resource
     *
     * @param EntryListBuilderInterface $entries
     */
    public function collectEntryLists(EntryListBuilderInterface $builder)
    {
        $resource = $builder->getResource();

        $node = $builder->getObject();
        if (!$node instanceof NodeInterface) {
            $node = $this->entityManager->getStorage('node')->load($resource->getId());
            if (!$node) {
                return;
            }
        }

        // This is where it gets complicated.
        $readOnly     = [Permission::VIEW];
        $readWrite    = [Permission::VIEW, Permission::UPDATE, Permission::DELETE];
        $readUpdate   = [Permission::VIEW, Permission::UPDATE];
        $isGlobal     = $node->is_global;
        $isCorporate  = $node->is_corporate;
        $isGhost      = $node->is_ghost;
        $isNotLocal   = $isGlobal || $isCorporate;
        $isPublished  = $node->isPublished();
        $groupId      = $node->group_id ?? Access::ID_ALL;

        // People with "view all" permissions should view it
        $builder->add(Access::PROFILE_READONLY, $groupId, $readOnly);
        $builder->add(Access::PROFILE_GOD, Access::ID_ALL, $readWrite);

        // This handles two grants in one:
        //  - Webmasters can browse along published content of other sites
        //  - People with global repository access may see this content

        if ($isCorporate) {
            $builder->add(Access::PROFILE_CORPORATE_ADMIN, $groupId, $readWrite);
            $builder->add(Access::PROFILE_CORPORATE_ADMIN, $groupId, [Permission::LOCK, Permission::PUBLISH]);
            $builder->add(Access::PROFILE_CORPORATE_ADMIN, $groupId, Access::ACL_PERM_CONTENT_PROMOTE_CORPORATE);
            if ($isPublished) { // Avoid data volume exploding
                $builder->add(Access::PROFILE_CORPORATE_READER, $groupId, $readOnly);
            }
        }
        if ($isGlobal) {
            $builder->add(Access::PROFILE_GLOBAL, $groupId, $readWrite);
            $builder->add(Access::PROFILE_GLOBAL, $groupId, [Permission::LOCK, Permission::PUBLISH]);
            $builder->add(Access::PROFILE_CORPORATE_ADMIN, $groupId, Access::ACL_PERM_CONTENT_PROMOTE_CORPORATE);
            if ($isPublished) { // Avoid data volume exploding
                $builder->add(Access::PROFILE_GLOBAL_READONLY, $groupId, $readOnly);
            }
        }

        // Non-ghost content is shared between groups, thus can be seen by
        // anyone as long as it is published. Unpublished content can never
        // be seen out of its group.
        if ($isPublished && !$isGhost) {
            if ($isCorporate) {
                $builder->add(Access::PROFILE_CORPORATE_READER, Access::ID_ALL, $readOnly);
            }
            if ($isGlobal) {
                $builder->add(Access::PROFILE_GLOBAL_READONLY, Access::ID_ALL, $readOnly);
            }
            if (!$isNotLocal) {
                $builder->add(Access::PROFILE_READONLY, Access::ID_ALL, $readOnly);
            }
        }

        if (!$isNotLocal) {
            // This allows other webmasters to see other site content, but please
            // beware that it drops out the site's state from the equation, there
            // is no easy way of doing this except by rewriting all site content
            // node access rights on each site status change, and that's sadly a
            // no-go.
            if ($isPublished) {
                $builder->add(Access::PROFILE_OTHER, $groupId, $readOnly);
            }

            // Every local node must be updateable for their authors, especially
            // for the contributor case, which don't have any other rights.
            $builder->add(Access::PROFILE_OWNER, $node->getOwnerId(), $readUpdate);

            // Node is neither global nor local, then it's webmasters that can
            // only do the following things over it
            if ($node->site_id) {
                $builder->add(Access::PROFILE_SITE_WEBMASTER, $node->site_id, [Permission::LOCK]);
            }
        }

        if (!$groupId) {
            // A content without a group identifier is considered as an
            // orphan content, even thought it belongs to a site: allow platform
            // admins to see it.
            $builder->add(Access::PROFILE_GROUP_ORPHAN_READER, Access::ID_ALL, $readOnly);
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
                    $builder->add(Access::PROFILE_PUBLIC, $siteId, $readOnly);
                }

                // This grand allows multiple business use cases:
                //   - user is a global administrator and can see everything
                //   - user is a contributor on a specific site
                //   - user is a webmaster on a readonly site
                if ($isNotLocal) {
                    if ($isPublished) {
                        $builder->add(Access::PROFILE_SITE_READONLY, $siteId, $readOnly);
                        $builder->add(Access::PROFILE_SITE_WEBMASTER, $siteId, $readOnly);
                    }
                } else  {
                    $builder->add(Access::PROFILE_SITE_READONLY, $siteId, $readOnly);
                    if ($siteId === $node->site_id) { // Avoid data volume exploding
                        $builder->add(Access::PROFILE_SITE_WEBMASTER, $siteId, $readWrite);
                        $builder->add(Access::PROFILE_SITE_WEBMASTER, $siteId, Permission::PUBLISH);
                    }
                }
            }
        }
    }

    /**
     * Collect entries for resource
     *
     * @param ProfileSetBuilder $builder
     */
    public function collectProfiles(ProfileSetBuilder $builder)
    {
        $account = $builder->getObject();
        if (!$account instanceof AccountInterface) {
            return;
        }

        // This should always be true anyway.
        if (($site = $this->siteManager->getContext()) && SiteState::ON === $site->getState()) {
            $builder->add(Access::PROFILE_PUBLIC, $site->getId());
        }

        // Shortcut for anonymous users, or users with no specific roles
        if ($account->isAnonymous()) {
            return;
        }

        // God mode.
        if ($account->hasPermission(Access::PERM_CONTENT_GOD)) {
            $builder->add(Access::PROFILE_GOD, Access::ID_ALL);
            return;
        }

        // User should always be able to edit its own content, I guess.
        $builder->add(Access::PROFILE_OWNER, $account->id());

        if ($account->hasPermission(Access::PERM_CONTENT_MANAGE_GLOBAL)) {
            $builder->add(Access::PROFILE_GLOBAL, Access::ID_ALL);
        }
        if ($account->hasPermission(Access::PERM_CONTENT_MANAGE_CORPORATE)) {
            $builder->add(Access::PROFILE_CORPORATE_ADMIN, Access::ID_ALL);
        }

        if ($account->hasPermission(Access::PERM_CONTENT_VIEW_ALL)) {
            $builder->add(Access::PROFILE_READONLY, Access::ID_ALL);
        } else {
            if ($account->hasPermission(Access::PERM_CONTENT_VIEW_GLOBAL) || $account->hasPermission(Access::PERM_CONTENT_MANAGE_GLOBAL)) {
                $builder->add(Access::PROFILE_GLOBAL_READONLY, Access::ID_ALL);
            }
            if ($account->hasPermission(Access::PERM_CONTENT_VIEW_CORPORATE) || $account->hasPermission(Access::PERM_CONTENT_MANAGE_CORPORATE)) {
                $builder->add(Access::PROFILE_CORPORATE_READER, Access::ID_ALL);
            }
            if ($account->hasPermission(Access::PERM_CONTENT_VIEW_OTHER)) {
                $builder->add(Access::PROFILE_OTHER, Access::ID_ALL);
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
                        $builder->add(Access::PROFILE_SITE_WEBMASTER, $siteId);
                        // It is required to set the readonly realm as well since there
                        // might be content referenced in site, but not belonging to it,
                        // which means they would then be invisibile to the webmasters.
                        $builder->add(Access::PROFILE_SITE_READONLY, $siteId);
                        break;

                    case SiteState::ARCHIVE:
                        $builder->add(Access::PROFILE_SITE_READONLY, $siteId);
                        break;
                }
            } else {
                switch ($grant->getSiteState()) {

                    case SiteState::ON:
                    case SiteState::OFF:
                        $builder->add(Access::PROFILE_SITE_READONLY, $siteId);
                        break;
                }
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

        if ('create' === $op) {

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
        if (Permission::UPDATE === $op && $account->uid && $node->uid == $account->uid) {
            if ($node->ucms_sites) {
                // Site contributors can update their own content in sites
                foreach ($access->getUserRoles($account) as $grant) {
                    if (in_array($grant->getSiteId(), $node->ucms_sites)) {
                        return $event->allow();
                    }
                }
            }
        }

        if (Permission::CLONE === $op) {
            if ($node->ucms_sites) {
                foreach (array_intersect_key($access->getUserRoles($account), array_flip($node->ucms_sites)) as $role) {
                    if ($role->getRole() == Access::ROLE_WEBMASTER) {
                        return true;
                    }
                }
            }
        }

        return $event->ignore();
    }
}
