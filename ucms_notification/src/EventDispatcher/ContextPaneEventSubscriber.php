<?php

namespace MakinaCorpus\Ucms\Notification\EventDispatcher;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\APubSub\CursorInterface;
use MakinaCorpus\APubSub\Field;
use MakinaCorpus\APubSub\Notification\NotificationService;
use MakinaCorpus\Drupal\Calista\EventDispatcher\ContextPaneEvent;
use MakinaCorpus\Ucms\Layout\ContextManager as LayoutContextManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContextPaneEventSubscriber implements EventSubscriberInterface
{
    use StringTranslationTrait;

    /**
     * @var LayoutContextManager
     */
    private $layoutContextManager;

    /**
     * @var AccountInterface
     */
    private $currentUser;

    /**
     * @var NotificationService
     */
    private $notificationService;

    /**
     * Default constructor
     *
     * @param LayoutContextManager $layoutContextManager
     * @param AccountInterface $currentUser
     * @param NotificationService $notificationService
     */
    public function __construct(LayoutContextManager $layoutContextManager, AccountInterface $currentUser, NotificationService $notificationService)
    {
        $this->layoutContextManager = $layoutContextManager;
        $this->currentUser = $currentUser;
        $this->notificationService = $notificationService;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ContextPaneEvent::EVENT_INIT => [
                ['onContextPaneInit', 0],
            ],
        ];
    }

    /**
     * Event: On ContextPane init.
     *
     * @param ContextPaneEvent $event
     */
    public function onContextPaneInit(ContextPaneEvent $event)
    {
        $contextPane = $event->getContextPane();

        $subscriber = $this->notificationService->getSubscriber($this->currentUser->id());
        $limit = 10;

        $cursor = $subscriber
            ->fetch()
            ->addSort(Field::MSG_SENT, CursorInterface::SORT_DESC)
            ->addSort(Field::MSG_ID, CursorInterface::SORT_DESC)
            ->setRange($limit, 0)
        ;

        $unreadCount = $subscriber->fetch([Field::MSG_UNREAD => 1])->count();

        $contextPane
            ->addTab('notification', $this->t("Notifications"), 'bell', 0, $unreadCount)
            ->add(
                notification_block_render_messages($cursor, true), // @todo hardcoded funtion call, twig template would have been better
                'notification'
            )
        ;

        // Set default tab on dashboard
        if (current_path() == 'admin/dashboard') {
            $contextPane->setDefaultTab('notification');
        }
    }
}
