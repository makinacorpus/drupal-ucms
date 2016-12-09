<?php

namespace MakinaCorpus\Ucms\Notification\EventDispatcher;

use MakinaCorpus\Ucms\Notification\NotificationService;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvents;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SiteEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var NotificationService
     */
    private $service;

    /**
     * Default constructor
     *
     * @param NotificationService $notificationService
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->service = $notificationService;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            SiteEvents::EVENT_CREATE => [
                ['onSiteCreate', 0],
            ],
            SiteEvents::EVENT_WEBMASTER_CREATE => [
                ['onWebmasterCreate', 0],
            ],
            SiteEvents::EVENT_WEBMASTER_ATTACH => [
                ['onWebmasterAttach', 0],
            ],
            SiteEvents::EVENT_WEBMASTER_REMOVE => [
                ['onWebmasterRemove', 0],
            ],
        ];
    }

    /**
     * Event: when adding a site.
     *
     * @param SiteEvent $event
     */
    public function onSiteCreate(SiteEvent $event)
    {
        $site = $event->getSite();

        $this->service->getNotificationService()->subscribe('site', $site->getId(), $site->getOwnerUserId());
    }
    /**
     * Event: when adding an user to a site.
     *
     * @param SiteEvent $event
     */
    public function onWebmasterCreate(SiteEvent $event)
    {
        $site = $event->getSite();
        $uid  = $event->getArgument('webmaster_id');

        $this->service->getNotificationService()->subscribe('site', $site->getId(), $uid);
    }

    /**
     * Event: when adding an user to a site.
     *
     * @param SiteEvent $event
     */
    public function onWebmasterAttach(SiteEvent $event)
    {
        $this->onWebmasterCreate($event);
    }

    /**
     * Event: when removing an user from a site.
     *
     * @param SiteEvent $event
     */
    public function onWebmasterRemove(SiteEvent $event)
    {
        $site = $event->getSite();
        $uid  = $event->getArgument('webmaster_id');

        $this->service->deleteSubscriptionsFor($uid, ['site:' . $site->getId()]);
    }
}
