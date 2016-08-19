<?php

namespace MakinaCorpus\Ucms\Group\EventDispatcher;

use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeAccessGrantEvent;
use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeAccessRecordEvent;
use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeEvent;
use MakinaCorpus\Ucms\Group\GroupManager;
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
     * Read only on any content in group
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
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        // Priority here ensures it happens after the 'ucms_site' node event
        // subscriber, and that we will have all site information set
        return [
            NodeEvent::EVENT_PREPARE => [
                ['onPrepare', 10]
            ],
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
    }

    /**
     * Set ghost property on nodes depending on group context
     */
    public function onPrepare(NodeEvent $event)
    {
        $node = $event->getNode();

        if ($node->isNew() && $this->siteManager->hasContext()) {
            // Set the is_ghost property according to site it's being inserted in
            // @todo
        }
    }

    /**
     * Compute node access records
     */
    public function onNodeAccessRecord(NodeAccessRecordEvent $event)
    {
        $node = $event->getNode();

        // Nodes are node, records are not contextual, just lookup for every
        // group the node belongs to and set those.
        $groupIdList = $this->groupManager->getAccess()->getNodeGroups($node);

        if (!$groupIdList) {
            return;
        }

        // You should read the following method documentation, but we are going
        // to do exactly the same, in order to isolate nodes from the global
        // (no group) context, we are going to remove the 'ucms_site' global
        // default permissions.
        foreach ([
            NodeAccess::REALM_GLOBAL,
            NodeAccess::REALM_GLOBAL_READONLY,
            NodeAccess::REALM_GROUP,
            NodeAccess::REALM_GROUP_READONLY,
            NodeAccess::REALM_OTHER,
        ] as $realm) {
            $event->removeWholeRealm($realm);
        }

        // And set back the real realms for our nodes!
        foreach ($groupIdList as $groupId) {
            $isPublished = $node->isPublished();

            if ($node->is_global) {
                $event->add(self::REALM_GROUP_GLOBAL, $groupId, true, true, true);
                if ($isPublished) { // Avoid data volume exploding
                    $event->add(self::REALM_GROUP_GLOBAL_READONLY, $groupId, $node->isPublished());
                }
            }
            if ($node->is_group) {
                $event->add(self::REALM_GROUP_GROUP, $groupId, true, true, true);
                if ($isPublished) { // Avoid data volume exploding
                    $event->add(self::REALM_GROUP_GROUP_READONLY, $groupId, $node->isPublished());
                }
            }

            if ($isPublished) {
                $event->add(self::REALM_GROUP_OTHER, $groupId);
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
        $userAccessList = $this->groupManager->getAccess()->getUserGroups($event->getAccount());

        // A user with groups may only see content from his own group whereas
        // a user with no group may only see all content; it's the site admin
        // responsabilities to ensure that no one has no groups.
        if (!$userAccessList) {
            // User has no group, leave it unchanged
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
            NodeAccess::REALM_OTHER,
        ] as $realm) {
            $event->removeWholeRealm($realm);
        }

        // 2. Add inside group permissions from this module.
        foreach ($userAccessList as $access) {
            /** @var \MakinaCorpus\Ucms\Group\GroupMember $access */
            $groupId = $access->getGroupId();

            if (false) { // can edit global
                $event->add(self::REALM_GLOBAL, $groupId);
            } else if (false) { // van view global
                $event->add(self::REALM_GLOBAL_READONLY, $groupId);
            }
            if (false) { // can edit group
                $event->add(self::REALM_GROUP, $groupId);
            } else if (false) { // can view group
                $event->add(self::REALM_GROUP_READONLY, $groupId);
            }

            $event->add(self::REALM_GROUP_OTHER, $groupId);
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
    }
}
