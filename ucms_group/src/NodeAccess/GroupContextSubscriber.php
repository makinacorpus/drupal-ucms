<?php

namespace MakinaCorpus\Ucms\Group\NodeAccess;

use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeAccessGrantEvent;
use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeAccessRecordEvent;
use MakinaCorpus\Ucms\Group\GroupAccess;
use MakinaCorpus\Ucms\Group\GroupManager;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\NodeAccess\NodeAccessEventSubscriber as NodeAccess;
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
     * Orphan content
     */
    const REALM_GROUP_ORPHAN = 'ucms_group_orphan';

    /**
     * Visible (not ghost) content
     */
    const REALM_GROUP_SHARED = 'ucms_group_shared';

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
     * Fetch the list of realms this module modifies
     *
     * @return string[]
     */
    private function getAlteredRealms()
    {
        return [
            NodeAccess::REALM_GLOBAL,
            NodeAccess::REALM_GLOBAL_READONLY,
            NodeAccess::REALM_GROUP,
            NodeAccess::REALM_GROUP_READONLY,
            NodeAccess::REALM_OTHER,
            NodeAccess::REALM_READONLY,
        ];
    }

    /**
     * Compute node access records
     */
    public function onNodeAccessRecord(NodeAccessRecordEvent $event)
    {
        $node = $event->getNode();

        $alteredRealms = $this->getAlteredRealms();

        if (!empty($node->group_id)) {

            // We will re-use the realms from 'ucms_site' but changing the default
            // gid to group identifiers instead, and make the whole isolation thing
            // completly transparent. Ghost nodes cannot be seen in global realms,
            // so we are just going to replace their realm identifiers using the
            // ones from the group it's in.
            $event->replaceGroupId($alteredRealms, NodeAccess::GID_DEFAULT, $node->group_id);

            if (!$node->is_ghost && $node->isPublished()) {

                // But sadly, non ghost nodes should be seen outside, so we
                // actually do need to restore them rights, at least in
                // readonly mode. Please see how the 'ucms_site' module sets
                // them to understand, we are only going to deal with readonly
                // rights.
                $event->add(NodeAccess::REALM_READONLY, NodeAccess::GID_DEFAULT);

                // This handles two grants in one:
                //  - Webmasters can browse along published content of other sites
                //  - People with global repository access may see this content
                if ($node->is_group) {
                    $event->add(NodeAccess::REALM_GROUP_READONLY, NodeAccess::GID_DEFAULT);
                } else if ($node->is_global) {
                    $event->add(NodeAccess::REALM_GLOBAL_READONLY, NodeAccess::GID_DEFAULT);
                } else {
                    $event->add(NodeAccess::REALM_OTHER, NodeAccess::GID_DEFAULT);
                }
            }
        } else {
            $event->removeWholeRealm($alteredRealms);

            // This node cannot be seen anywhere, we just give the global
            // platform administrators the right to see it
            return $event->add(self::REALM_GROUP_ORPHAN, NodeAccess::GID_DEFAULT);
        }
    }

    /**
     * Compute user grants
     */
    public function onNodeAccessGrant(NodeAccessGrantEvent $event)
    {
        $account = $event->getAccount();

        // God mode.
        if ($account->hasPermission(Access::PERM_CONTENT_GOD)) {
            return;
        }

        // Some users have global permissions on the platform, we need to give
        // them the right to see orphan content.
        if ($account->hasPermission(GroupAccess::PERM_MANAGE_ORPHAN)) {
            $event->add(self::REALM_GROUP_ORPHAN, NodeAccess::GID_DEFAULT);
        }

        // Note that we won't change anything about site rights.

        // Then replicate all user permissions, but relative to groups.
        foreach ($this->groupManager->getAccess()->getUserGroups($account) as $access) {

            /** @var \MakinaCorpus\Ucms\Group\GroupMember $access */
            $groupId = $access->getGroupId();
            // @todo view all permission is global
            $viewAll = $account->hasPermission(Access::PERM_CONTENT_VIEW_ALL);

            if ($viewAll) {
                $event->add(NodeAccess::REALM_READONLY, $groupId);
            }

            if ($account->hasPermission(Access::PERM_CONTENT_MANAGE_GLOBAL)) {
                $event->add(NodeAccess::REALM_GLOBAL, $groupId);
            }
            if ($account->hasPermission(Access::PERM_CONTENT_MANAGE_GROUP)) {
                $event->add(NodeAccess::REALM_GROUP, $groupId);
            }

            if ($account->hasPermission(Access::PERM_CONTENT_VIEW_ALL)) {
                $event->add(NodeAccess::REALM_READONLY, $groupId);
            } else {
                if ($account->hasPermission(Access::PERM_CONTENT_VIEW_GLOBAL)) {
                    $event->add(NodeAccess::REALM_GLOBAL_READONLY, $groupId);
                }
                if ($account->hasPermission(Access::PERM_CONTENT_VIEW_GROUP)) {
                    $event->add(NodeAccess::REALM_GROUP_READONLY, $groupId);
                }
                if ($account->hasPermission(Access::PERM_CONTENT_VIEW_OTHER)) {
                    $event->add(NodeAccess::REALM_OTHER, $groupId);
                }
            }
        }
    }

    /**
     * Set current group context
     */
    public function onSiteInit(SiteEvent $event)
    {
        $group = $this->groupManager->getAccess()->getSiteGroup($event->getSite());

        if ($group) {
            $this->siteManager->setDependentContext('group', $group);
        }
    }
}
