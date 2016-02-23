<?php

namespace MakinaCorpus\Ucms\Notification\EventDispatcher;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\EventDispatcher\ContextPaneEvent;
use MakinaCorpus\Ucms\Layout\Context as LayoutContext;

class ContextPaneEventListener
{
    use StringTranslationTrait;

    /**
     * @var LayoutContext
     */
    private $layoutContext;

    /**
     * @var AccountInterface
     */
    private $currentUser;

    /**
     * Default constructor
     *
     * @param LayoutContext $layoutContext
     * @param AccountInterface $currentUser
     */
    public function __construct(LayoutContext $layoutContext, AccountInterface $currentUser)
    {
        $this->layoutContext = $layoutContext;
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
                notification_block_render($this->currentUser),
                'notification'
            )
        ;

        // Set default tab on dashboard
        if (current_path() == 'admin/dashboard') {
            $contextPane->setDefaultTab('notification');
        }
    }
}
