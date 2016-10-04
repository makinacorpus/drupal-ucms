<?php

namespace MakinaCorpus\Ucms\Notification\EventDispatcher;

use MakinaCorpus\Ucms\Notification\NotificationService;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;

class SiteEventListener
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
     * Event: On adding a site.
     *
     * @param SiteEvent $event
     */
    public function onSiteCreate(SiteEvent $event)
    {
        $site = $event->getSite();

        $this->service->getNotificationService()->subscribe('site', $site->getId(), $site->getOwnerUserId());
    }
    /**
     * Event: On adding an user to a site.
     *
     * @param SiteEvent $event
     */
    public function onSiteWebmasteraddnew(SiteEvent $event)
    {
        $site = $event->getSite();
        $uid  = $event->getArgument('webmaster_id');

        $this->service->getNotificationService()->subscribe('site', $site->getId(), $uid);
    }

    /**
     * Event: On adding an user to a site.
     *
     * @param SiteEvent $event
     */
    public function onSiteWebmasteraddexisting(SiteEvent $event)
    {
        $this->onSiteWebmasteraddnew($event);
    }

    /**
     * Event: On deleting an user to a site.
     *
     * @param SiteEvent $event
     */
    public function onSiteWebmasterdelete(SiteEvent $event)
    {
        $site = $event->getSite();
        $uid  = $event->getArgument('webmaster_id');

        $this->service->deleteSubscriptionsFor($uid, ['site:' . $site->getId()]);
    }
}
