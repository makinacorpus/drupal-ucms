<?php

namespace MakinaCorpus\Ucms\Notification\EventDispatcher;

use Drupal\Core\Session\AccountInterface;
use MakinaCorpus\APubSub\Notification\EventDispatcher\ResourceEvent;
use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeEvent;
use MakinaCorpus\Ucms\Notification\NotificationService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This subscriber raises additional events on node changes, but does not
 * send any notifications by itself; everything is handled by the class:
 *
 * \MakinaCorpus\APubSub\Notification\EventDispatcher\AutoEventListener
 *
 * For it to work gracefully, and handlded additional contextes (such as groups)
 * another subscriber will alter the channel identifiers on notification send:
 *
 * \MakinaCorpus\Ucms\Site\EventDispatcher\NotificationContextEventSubscriber
 */
class NodeEventSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            NodeEvent::EVENT_INSERT => [
                ['onInsert', -1024]
            ],
            NodeEvent::EVENT_UPDATE => [
                ['onUpdateRunNodeEvents', -1024],
                ['onUpdateRunLabelEvents', -1024],
            ],
            NodeEvent::EVENT_DELETE => [
                ['onDelete', -1024]
            ],
        ];
    }

    private $service;
    private $currentUser;
    private $dispatcher;

    /**
     * @var \MakinaCorpus\Ucms\Site\SiteManager null
     */
    private $siteManager = null;

    /**
     * Default constructor
     *
     * @param NotificationService $service
     * @param AccountInterface $currentUser
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(NotificationService $service, AccountInterface $currentUser, EventDispatcherInterface $dispatcher)
    {
        $this->service = $service;
        $this->currentUser = $currentUser;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Set site manager
     *
     * @param \MakinaCorpus\Ucms\Site\SiteManager $siteManager
     */
    public function setSiteManager($siteManager)
    {
        $this->siteManager = $siteManager;
    }

    /**
     * From the given node, ensure subscribers
     *
     * @param NodeEvent $event
     */
    private function ensureNodeSubscribers(NodeEvent $event)
    {
        $node = $event->getNode();

        $followers = [];

        if ($userId = $node->getOwnerId()) {
            $followers[] = $userId;
        }
        if ($userId = $this->currentUser->id()) {
            $followers[] = $userId;
        }

        if ($this->siteManager && $node->site_id) {
            // Notify all webmasters for the site, useful for content modified by local contributors.
            $site = $this->siteManager->getStorage()->findOne($node->site_id);
            foreach ($this->siteManager->getAccess()->listWebmasters($site) as $webmaster) {
                $followers[] = $webmaster->getUserId();
            }
        }

        $this->service->getNotificationService()->subscribe('node', $node->id(), $followers);
    }

    /**
     * On node insert
     */
    public function onInsert(NodeEvent $event)
    {
        $node = $event->getNode();

        $this->ensureNodeSubscribers($event);

        // Enfore node event to run, so that the automatic resource listener
        // will raise the correct notifications
        $newEvent = new ResourceEvent('node', $node->nid, $this->currentUser->id());
        $this->dispatcher->dispatch('node:add', $newEvent);
    }

    /**
     * On node update raise node related notifications
     */
    public function onUpdateRunNodeEvents(NodeEvent $event)
    {
        $node = $event->getNode();

        $this->ensureNodeSubscribers($event);

        $newEvent = new ResourceEvent('node', $node->nid, $this->currentUser->id());

        if ($node->is_flagged != $node->original->is_flagged) {
            if ($node->is_flagged) {
                $this->dispatcher->dispatch('node:flag', $newEvent);
            }
        } else if ($node->status != $node->original->status) {
            if ($node->status) {
                $this->dispatcher->dispatch('node:publish', $newEvent);
            } else {
                $this->dispatcher->dispatch('node:unpublish', $newEvent);
            }
        } else {
            $this->dispatcher->dispatch('node:edit', $newEvent);
        }
    }

    /**
     * On node update raise label related notifications
     */
    public function onUpdateRunLabelEvents(NodeEvent $event)
    {
        $node = $event->getNode();

        if ($oldLabels = field_get_items('node', $node->original, 'labels')) {
            $oldLabels = array_column($oldLabels, 'tid');
        } else {
            $oldLabels = [];
        }

        if ($currentLabels = field_get_items('node', $node, 'labels')) {
            if (is_array($currentLabels)) {
                $currentLabels = array_column($currentLabels, 'tid');
            }
            else {
                $currentLabels = (array) $currentLabels;
            }
        }

        if ($currentLabels && ($newLabels = array_diff($currentLabels, $oldLabels))) {
            $newEvent = new ResourceEvent('node', $node->nid, $this->currentUser->id());
            $newEvent->setArgument('new_labels', $newLabels);
            $this->dispatcher->dispatch('node:new_labels', $newEvent);
        }
    }

    /**
     * On node delete
     */
    public function onDelete(NodeEvent $event)
    {
        $this->service->getNotificationService()->getBackend()->deleteChannel('node:' . $event->getEntityId());
    }
}
