<?php

namespace MakinaCorpus\Ucms\Group\EventDispatcher;

use Drupal\Core\Session\AccountInterface;

use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeAccessGrantEvent;
use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeAccessRecordEvent;
use MakinaCorpus\Ucms\Group\GroupManager;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\EventDispatcher\NodeAccessEventSubscriber as NodeAccess;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvents;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * All context alterations events into the same subscriber, because it does
 * not mean anything to disable one or the other, it's all or nothing.
 */
class GroupContextSubscriber implements EventSubscriberInterface
{
    /**
     * Read/write on group content in group
     */
    const REALM_GROUP_GROUP = 'ucms_group_group';

    /**
     * Read only on group content in group
     */
    const REALM_GROUP_GROUP_READONLY = 'ucms_group_group_ro';

    /**
     * Read/write on global content in group
     */
    const REALM_GROUP_GLOBAL = 'ucms_group_global';

    /**
     * Read only on global content in group
     */
    const REALM_GROUP_GLOBAL_READONLY = 'ucms_group_global_ro';

    /**
     * View all content, god realm
     */
    const REALM_GROUP_READONLY = 'ucms_group_ro';

    /**
     * Read/write on global content in group
     */
    const REALM_GROUP_OTHER = 'ucms_group_other';

    /**
     * @var SiteManager
     */
    private $siteManager;

    /**
     * @var GroupManager
     */
    private $groupManager;

    /**
     * @var mixed[]
     */
    private $userGrantCache;

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        // Priority here ensures it happens after the 'ucms_site' node event
        // subscriber, and that we will have all site information set
        return [
            NodeAccessRecordEvent::EVENT_NODE_ACCESS_RECORD => [
                ['onNodeAccessRecord', -128],
            ],
            NodeAccessGrantEvent::EVENT_NODE_ACCESS_GRANT => [
                ['onNodeAccessGrant', -128],
            ],
            SiteEvents::EVENT_INIT => [
                ['onSiteInit', 0],
            ],
        ];
    }

    /**
     * Default constructor
     *
     * @param SiteManager $siteManager
     * @param GroupManager $groupManager
     */
    public function __construct(SiteManager $siteManager, GroupManager $groupManager)
    {
        $this->siteManager = $siteManager;
        $this->groupManager = $groupManager;
        // Sorry for this, but we do need it to behave with Drupal internals
        $this->userGrantCache = &drupal_static('ucms_site_node_grants', []);
    }

    /**
     * Reset internal cache
     */
    public function resetCache()
    {
        drupal_static_reset('node_access');
        drupal_static_reset('ucms_site_node_grants');
        $this->userGrantCache = &drupal_static('ucms_site_node_grants', []);
    }

    private function buildNodeAccessGrant(AccountInterface $account, $op)
    {
        // @todo implement me
    }

    /**
     * Compute node access records
     */
    public function onNodeAccessRecord(NodeAccessRecordEvent $event)
    {
        $node = $event->getNode();

        if (empty($node->group_id)) {
            return;
        }

        /*
         * Here are our specification and use cases:
         *
         *   - node has 'group_id' set (it belongs to the group): add visibility
         *     for the group users, depending on their roles (see realms);
         *
         *   - node has a 'group_id' and 'is_ghost': all visibility permissions
         *     should be dropped outside of sites where it's being referenced;
         *
         *   - node has NO 'group_id', nothing changes for it, group members
         *     will not have the global permissions, so they will not be able
         *     to see anywhere except in sites they can see it;
         *
         *   - there is a special use case group but no sites
         */

        // Node has 'is_ghost' drop global visibility
        // Otherwise, global visibility remains
        if ($node->is_ghost) {
            foreach ([
                NodeAccess::REALM_GLOBAL,
                NodeAccess::REALM_GLOBAL_READONLY,
                NodeAccess::REALM_GROUP,
                NodeAccess::REALM_GROUP_READONLY,
                NodeAccess::REALM_OTHER,
            ] as $realm) {
                $event->removeWholeRealm($realm);
            }
        }

        // People with the view all content permission should be able to see it
        $event->add(self::REALM_GROUP_READONLY, $node->group_id, true);

        // And set back the real realms for our nodes!
        $isPublished = $node->isPublished();

        if ($node->is_global) {
            $event->add(self::REALM_GROUP_GLOBAL, $node->group_id, true, true, true);
            if ($isPublished) { // Avoid data volume exploding
                $event->add(self::REALM_GROUP_GLOBAL_READONLY, $node->group_id, $node->isPublished());
            }
        }
        if ($node->is_group) {
            $event->add(self::REALM_GROUP_GROUP, $node->group_id, true, true, true);
            if ($isPublished) { // Avoid data volume exploding
                $event->add(self::REALM_GROUP_GROUP_READONLY, $node->group_id, $node->isPublished());
            }
        }

        // Please note that in no case we need to drop the node grants, if any
        // group gets deleted, the SQL query conditions will just not match even
        // if data remains into database, and for what it worth, it will be
        // dropped on next node update, and I'm definitely fine with that.
    }

    /**
     * Compute user grants
     */
    public function onNodeAccessGrant(NodeAccessGrantEvent $event)
    {
        $account = $event->getAccount();
        $userAccessList = $this->groupManager->getAccess()->getUserGroups($account);

        // A user with groups may only see content from his own group whereas
        // a user with no group may only see all content; it's the site admin
        // responsabilities to ensure that no one has no groups.
        if (!$userAccessList) {
            return;
        }

        // Since we are working on top of the 'ucms_site' module, we do need
        // to alter whatever grant it set before us, and remove all of the
        // grants that are not appliable inside this very same group.

        // We have the following "global" realms defined by the 'ucms_site'
        // module that we are going to remove, so that the user may not see
        // anything from it:
        //
        //  - REALM_GLOBAL
        //  - REALM_GLOBAL_READONLY
        //  - REALM_GROUP
        //  - REALM_GROUP_READONLY
        //
        // All other realms are already contextual, mostly by site, so we
        // don't care about those, as long as a user is webmaster inside his
        // own site, he can see everything.

        // 1. Remove global permissions from 'ucms_site' module.
        foreach ([
            NodeAccess::REALM_GLOBAL,
            NodeAccess::REALM_GLOBAL_READONLY,
            NodeAccess::REALM_GROUP,
            NodeAccess::REALM_GROUP_READONLY,
            NodeAccess::REALM_READONLY,
            // Disallow non-group/non-site/non-global nodes to be seen
            NodeAccess::REALM_OTHER,
        ] as $realm) {
            $event->removeWholeRealm($realm);
        }

        // 2. Add inside group permissions from this module.
        foreach ($userAccessList as $access) {
            /** @var \MakinaCorpus\Ucms\Group\GroupMember $access */
            $groupId = $access->getGroupId();
            $viewAll = $account->hasPermission(Access::PERM_CONTENT_VIEW_ALL);

            if ($viewAll) {
                $event->add(self::REALM_GROUP_READONLY, $groupId);
            }

            if ($account->hasPermission(Access::PERM_CONTENT_MANAGE_GLOBAL)) { // can edit global
                $event->add(self::REALM_GROUP_GLOBAL, $groupId);
            } else if (!$viewAll && $account->hasPermission(Access::PERM_CONTENT_VIEW_GLOBAL)) { // van view global
                $event->add(self::REALM_GROUP_GLOBAL_READONLY, $groupId);
            }
            if ($account->hasPermission(Access::PERM_CONTENT_MANAGE_GROUP)) { // can edit group
                $event->add(self::REALM_GROUP_GROUP, $groupId);
            } else if (!$viewAll && $account->hasPermission(Access::PERM_CONTENT_VIEW_GROUP)) { // can view group
                $event->add(self::REALM_GROUP_GROUP_READONLY, $groupId);
            }
        }
    }

    /**
     * Set current group context
     */
    public function onSiteInit(SiteEvent $event)
    {
        $accessList = $this->groupManager->getAccess()->getSiteGroups($event->getSite(), false);

        if ($accessList) {
            $this->siteManager->setDependentContext('group', $accessList);
        }

        $this->resetCache();
    }
}
