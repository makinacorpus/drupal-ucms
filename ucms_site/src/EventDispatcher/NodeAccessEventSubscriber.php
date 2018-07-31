<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeAccessEvent;
use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeAccessGrantEvent;
use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeAccessRecordEvent;
use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeAccessSubscriber as NodeAccessCache;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\GroupManager;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvents;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteInitEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Uses the abstraction provided by the sf_dic module to collect node access
 * grants and user grants, so benefit from the generic method it provides
 * to intersect those at runtime.
 */
final class NodeAccessEventSubscriber implements EventSubscriberInterface
{
    private $groupManager;
    private $siteManager;
    private $nodeAccessCache;

    /**
     * Default constructor
     */
    public function __construct(SiteManager $siteManager, GroupManager $groupManager, NodeAccessCache $nodeAccessCache)
    {
        $this->groupManager = $groupManager;
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
            /*
            NodeAccessEvent::EVENT_NODE_ACCESS => [
                ['onNodeAccess', 48],
            ],
            SiteEvents::EVENT_INIT => [
                ['onSiteInit', 0],
            ],
             */
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
    public function onNodeAccessRecord(NodeAccessRecordEvent $builder)
    {
        $node = $builder->getNode();

        // This is where it gets complicated.
        /*
        $readOnly     = [Access::OP_VIEW];
        $readWrite    = [Access::OP_VIEW, Access::OP_UPDATE, Access::OP_DELETE];
        $readUpdate   = [Access::OP_VIEW, Access::OP_UPDATE];
         */
        $isGlobal     = (bool)$node->get('is_global')->value;
        $isCorporate  = (bool)$node->get('is_corporate')->value;
        $isGhost      = (bool)$node->get('is_ghost')->value;
        $isNotLocal   = $isGlobal || $isCorporate;
        $isPublished  = $node->isPublished();
        $groupId      = (int)($node->get('group_id')->value ?? Access::ID_ALL);

        // People with "view all" permissions should view it
        $builder->add(Access::PROFILE_READONLY, $groupId, true, false, false /* $readOnly */);
        $builder->add(Access::PROFILE_GOD, Access::ID_ALL, true, true, true /* $readWrite */);

        // This handles two grants in one:
        //  - Webmasters can browse along published content of other sites
        //  - People with global repository access may see this content

        if ($isCorporate) {
            $builder->add(Access::PROFILE_CORPORATE_ADMIN, $groupId, true, true, true /* $readWrite */);
            /*
            $builder->add(Access::PROFILE_CORPORATE_ADMIN, $groupId, [Permission::LOCK, Permission::PUBLISH]);
            $builder->add(Access::PROFILE_CORPORATE_ADMIN, $groupId, Access::ACL_PERM_CONTENT_PROMOTE_CORPORATE);
             */
            if ($isPublished) { // Avoid data volume exploding
                $builder->add(Access::PROFILE_CORPORATE_READER, $groupId, true, false, false /* $readOnly */);
            }
        }
        if ($isGlobal) {
            $builder->add(Access::PROFILE_GLOBAL, $groupId, true, true, true /* $readWrite */);
            /*
            $builder->add(Access::PROFILE_GLOBAL, $groupId, [Permission::LOCK, Permission::PUBLISH]);
            $builder->add(Access::PROFILE_CORPORATE_ADMIN, $groupId, Access::ACL_PERM_CONTENT_PROMOTE_CORPORATE);
             */
            if ($isPublished) { // Avoid data volume exploding
                $builder->add(Access::PROFILE_GLOBAL_READONLY, $groupId, true, false, false /* $readOnly */);
            }
        }

        // Non-ghost content is shared between groups, thus can be seen by
        // anyone as long as it is published. Unpublished content can never
        // be seen out of its group.
        if ($isPublished && !$isGhost) {
            if ($isCorporate) {
                $builder->add(Access::PROFILE_CORPORATE_READER, Access::ID_ALL, true, false, false /* $readOnly */);
            }
            if ($isGlobal) {
                $builder->add(Access::PROFILE_GLOBAL_READONLY, Access::ID_ALL, true, false, false /* $readOnly */);
            }
            if (!$isNotLocal) {
                $builder->add(Access::PROFILE_READONLY, Access::ID_ALL, true, false, false /* $readOnly */);
            }
        }

        if (!$isNotLocal) {
            // This allows other webmasters to see other site content, but please
            // beware that it drops out the site's state from the equation, there
            // is no easy way of doing this except by rewriting all site content
            // node access rights on each site status change, and that's sadly a
            // no-go.
            if ($isPublished) {
                $builder->add(Access::PROFILE_OTHER, $groupId, true, false, false /* $readOnly */);
            }

            // Every local node must be updateable for their authors, especially
            // for the contributor case, which don't have any other rights.
            $builder->add(Access::PROFILE_OWNER, $node->getOwnerId(), true, true, false /* $readUpdate */);

            // Node is neither global nor local, then it's webmasters that can
            // only do the following things over it
            /*
            if ($node->site_id) {
                $builder->add(Access::PROFILE_SITE_WEBMASTER, $node->site_id, [Permission::LOCK]);
            }
             */
        }

        if (!$groupId) {
            // A content without a group identifier is considered as an
            // orphan content, even thought it belongs to a site: allow platform
            // admins to see it.
            $builder->add(Access::PROFILE_GROUP_ORPHAN_READER, Access::ID_ALL, true, false, false /* $readOnly */);
        }

        // Inject an entry for each site, even when the node is a global node, this
        // will tell the Drupal API system if the node is visible or not inside a
        // local site. Please note that we will never add the site state as a node
        // grant, this will be determined at runtime: the reason for this is that if
        // you change a site state, you would need to rebuild all its nodes grants
        // and this would not be tolerable.
        $siteItems = $node->get('ucms_sites');
        if (!$siteItems->isEmpty()) {
            foreach (array_unique(array_column($siteItems->getValue(), 'value')) as $siteId) {

                // Grant that reprensents the node in the site for anonymous
                // as long as it exists, not may show up anytime when the site
                // state is on
                if ($isPublished) {
                    $builder->add(Access::PROFILE_PUBLIC, $siteId, true, false, false /* $readOnly */);
                }

                // This grand allows multiple business use cases:
                //   - user is a global administrator and can see everything
                //   - user is a contributor on a specific site
                //   - user is a webmaster on a readonly site
                if ($isNotLocal) {
                    if ($isPublished) {
                        $builder->add(Access::PROFILE_SITE_READONLY, $siteId, true, false, false /* $readOnly */);
                        $builder->add(Access::PROFILE_SITE_WEBMASTER, $siteId, true, false, false /* $readOnly */);
                    }
                } else  {
                    $builder->add(Access::PROFILE_SITE_READONLY, $siteId, true, false, false /* $readOnly */);
                    if ($siteId === (int)$node->get('site_id')->value) { // Avoid data volume exploding
                        $builder->add(Access::PROFILE_SITE_WEBMASTER, $siteId, true, false, false /* $readOnly */);
                        /*
                        $builder->add(Access::PROFILE_SITE_WEBMASTER, $siteId, Permission::PUBLISH);
                         */
                    }
                }
            }
        }
    }

    /**
     * Collect user grants method
     */
    public function onNodeAccessGrant(NodeAccessGrantEvent $builder)
    {
        $account = $builder->getAccount();

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

        // Some users have global permissions on the platform, we need to give
        // them the right to see orphan content when group are enabled.
        if ($account->hasPermission(Access::PERM_GROUP_MANAGE_ORPHAN)) {
            $builder->add(Access::PROFILE_GROUP_ORPHAN_READER, Access::ID_ALL);
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

        // Then replicate all user permissions, but relative to groups.
        // @todo this should be set using a relative group role instead..
        foreach ($this->groupManager->getUserGroups($account) as $access) {

            /** @var \MakinaCorpus\Ucms\Site\GroupMember $access */
            $groupId = $access->getGroupId();
            // @todo view all permission is global
            $viewAll = $account->hasPermission(Access::PERM_CONTENT_VIEW_ALL);

            if ($viewAll) {
                $builder->add(Access::PROFILE_READONLY, $groupId);
            }

            if ($account->hasPermission(Access::PERM_CONTENT_MANAGE_GLOBAL)) {
                $builder->add(Access::PROFILE_GLOBAL, $groupId);
            }
            if ($account->hasPermission(Access::PERM_CONTENT_MANAGE_CORPORATE)) {
                $builder->add(Access::PROFILE_CORPORATE_ADMIN, $groupId);
            }

            if ($account->hasPermission(Access::PERM_CONTENT_VIEW_ALL)) {
                $builder->add(Access::PROFILE_READONLY, $groupId);
            } else {
                if ($account->hasPermission(Access::PERM_CONTENT_VIEW_GLOBAL)) {
                    $builder->add(Access::PROFILE_GLOBAL_READONLY, $groupId);
                }
                if ($account->hasPermission(Access::PERM_CONTENT_VIEW_CORPORATE)) {
                    $builder->add(Access::PROFILE_CORPORATE_READER, $groupId);
                }
                if ($account->hasPermission(Access::PERM_CONTENT_VIEW_OTHER)) {
                    $builder->add(Access::PROFILE_OTHER, $groupId);
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
        if ('update' === $op && !$account->isAnonymous() && $node->getOwnerId() == $account->id()) {
            $siteItems = $node->get('ucms_sites');
            if (!$siteItems->isEmpty()) {
                $siteIdList = \array_column($siteItems->getValue(), 'value');
                // Site contributors can update their own content in sites
                foreach ($access->getUserRoles($account) as $grant) {
                    if (in_array($grant->getSiteId(), $siteIdList)) {
                        return $event->allow();
                    }
                }
            }
        }

        return $event->ignore();
    }

    /**
     * Checks node access for content creation
     * /
    public function onNodeAccess(NodeAccessEvent $event)
    {
        $node = $event->getNode();

        if (Access::OP_CREATE === $event->getOperation()) {
            if (is_string($node)) {
                if ($group = $this->findMostRelevantGroup()) {
                    $allowedContentTypes = $group->getAttribute('allowed_content_types');
                    if ($allowedContentTypes && !in_array($node, $allowedContentTypes)) {
                        return $event->deny();
                    }
                }
            }
        }

        return $event->ignore();
    }
     */
}
