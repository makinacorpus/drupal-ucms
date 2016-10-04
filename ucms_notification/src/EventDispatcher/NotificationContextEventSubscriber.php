<?php

namespace MakinaCorpus\Ucms\Notification\EventDispatcher;

use Drupal\Core\Entity\EntityManager;

use MakinaCorpus\APubSub\Notification\EventDispatcher\ResourceEvent;
use MakinaCorpus\Ucms\Group\GroupManager;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This class only alters notification events channels by adding potentially
 * missing contextes (for example, group identifiers).
 *
 * For more information about the whole process, read carefully inline API
 * documentation of the following class:
 *
 * \MakinaCorpus\Ucms\Site\EventDispatcher\NodeEventSubscriber
 */
class NotificationContextEventSubscriber implements EventSubscriberInterface
{
    const DEFAULT_PRIORITY = -1024;

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            'site:request' => [
                ['onSiteResourceEventAlterChannels', self::DEFAULT_PRIORITY],
            ],
            'site:switch' => [
                ['onSiteResourceEventAlterChannels', self::DEFAULT_PRIORITY],
            ],
            'node:add' => [
                ['onNodeResourceEventAlterChannels', self::DEFAULT_PRIORITY],
            ],
            'node:edit' => [
                ['onNodeResourceEventAlterChannels', self::DEFAULT_PRIORITY],
            ],
            'node:publish' => [
                ['onNodeResourceEventAlterChannels', self::DEFAULT_PRIORITY],
            ],
            'node:unpublish' => [
                ['onNodeResourceEventAlterChannels', self::DEFAULT_PRIORITY],
            ],
            'node:flag' => [
                ['onNodeResourceEventAlterChannels', self::DEFAULT_PRIORITY],
            ],
            'node:delete' => [
                ['onNodeResourceEventAlterChannels', self::DEFAULT_PRIORITY],
            ],
            'node:new_labels' => [
                ['onNodeResourceEventAlterChannels', self::DEFAULT_PRIORITY],
            ],
        ];
    }

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var SiteManager
     */
    private $siteManager;

    /**
     * @var GroupManager
     */
    private $groupManager;

    /**
     * Default constructor
     */
    public function __construct(EntityManager $entityManager, SiteManager $siteManager, GroupManager $groupManager = null)
    {
        $this->entityManager = $entityManager;
        $this->siteManager = $siteManager;
        $this->groupManager = $groupManager;
    }

    /**
     * On resource events, if applyable, attempt to alter channels to drop the
     * global subscription and set it to group channels
     */
    public function onSiteResourceEventAlterChannels(Event $event)
    {
        if (!$this->groupManager || !$event instanceof ResourceEvent) {
            return;
        }

        $done = [];

        // Fetch site groups, and yes we don't really have a choice here
        $sites = $this->siteManager->getStorage()->loadAll($event->getResourceIdList(), false);
        foreach ($sites as $site) {
            $groupId = (int)$site->getGroupId();
            if ($groupId) {
                $done[$groupId] = $groupId;
            }
        }

        foreach ($done as $groupId) {
            $event->addArbitraryChanId('admin:site:' . $groupId);
        }
    }

    /**
     * On resource events, if applyable, attempt to alter channels to drop the
     * global subscription and set it to group channels
     */
    public function onNodeResourceEventAlterChannels(Event $event)
    {
        if (!$this->groupManager || !$event instanceof ResourceEvent) {
            return;
        }

        $done = [];

        // Fetch site groups, and yes we don't really have a choice here
        $nodes = $this->entityManager->getStorage('node')->loadMultiple($event->getResourceIdList());
        foreach ($nodes as $node) {
            if ($node->group_id) {
                $groupId = (int)$node->group_id;
                $done[$groupId] = $groupId;
            }
        }

        foreach ($done as $groupId) {
            $event->addArbitraryChanId('admin:content:' . $groupId);
        }
    }
}
