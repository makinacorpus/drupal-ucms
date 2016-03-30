<?php

namespace MakinaCorpus\Ucms\Notification\EventDispatcher;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\EventDispatcher\ContextPaneEvent;
use MakinaCorpus\Ucms\Layout\ContextManager as LayoutContextManager;

class ContextPaneEventListener
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
     * Default constructor
     *
     * @param LayoutContextManager $layoutContextManager
     * @param AccountInterface $currentUser
     */
    public function __construct(LayoutContextManager $layoutContextManager, AccountInterface $currentUser)
    {
        $this->layoutContextManager = $layoutContextManager;
        $this->currentUser = $currentUser;
    }

    /**
     * Event: On ContextPane init.
     *
     * @param ContextPaneEvent $event
     */
    public function onUcmsdashboardContextinit(ContextPaneEvent $event)
    {
        $contextPane = $event->getContextPane();

        $contextPane
            ->addTab('notification', $this->t("Notifications"), 'bell')
            ->add(
                notification_block_render($this->currentUser, true),
                'notification'
            )
        ;

        // Set default tab on dashboard
        if (current_path() == 'admin/dashboard') {
            $contextPane->setDefaultTab('notification');
        }
    }
}
