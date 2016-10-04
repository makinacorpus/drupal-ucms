<?php

namespace MakinaCorpus\Ucms\Notification\EventDispatcher;

use MakinaCorpus\APubSub\Notification\NotificationService;
use MakinaCorpus\Ucms\Extranet\EventDispatcher\ExtranetMemberEvent;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExtranetMemberEventSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ExtranetMemberEvent::EVENT_REGISTER => [
                ['onExtranetMemberRegister', 0],
            ],
            ExtranetMemberEvent::EVENT_ACCEPT => [
                ['onExtranetMemberAccept', 0],
            ],
            ExtranetMemberEvent::EVENT_REJECT => [
                ['onExtranetMemberReject', 0],
            ],
        ];
    }

    /**
     * @var NotificationService
     */
    private $notificationService;

    /**
     * Constructor.
     *
     * @param NotificationService $notifService
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function onExtranetMemberRegister(ExtranetMemberEvent $event)
    {
        $this->notify($event, 'register');
    }

    public function onExtranetMemberAccept(ExtranetMemberEvent $event)
    {
        $this->notify($event, 'accept');
    }

    public function onExtranetMemberReject(ExtranetMemberEvent $event)
    {
        $this->notify($event, 'reject');
    }

    /**
     * Sends a notification on the site's channel.
     *
     * @param ExtranetMemberEvent $event
     * @param string $action
     */
    protected function notify(ExtranetMemberEvent $event, $action)
    {
        $userId = $event->getSubject()->id();
        $siteId = $event->getSite()->getId();

        $this->notificationService->notifyChannel('site:' . $siteId, 'member', $userId, $action, $event->getArguments());
    }
}
