<?php

namespace MakinaCorpus\Ucms\Notification\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\EventDispatcher\ContextPaneEvent;
use MakinaCorpus\Ucms\Layout\Context as LayoutContext;

/**
 * Class ContextPaneEventListener
 *
 * @package MakinaCorpus\Ucms\Notification\EventDispatcher
 */
class ContextPaneEventListener
{
    use StringTranslationTrait;

    /**
     * @var LayoutContext
     */
    private $layoutContext;

    /**
     * Default constructor
     *
     * @param LayoutContext $layoutContext
     */
    public function __construct(LayoutContext $layoutContext)
    {
        $this->layoutContext = $layoutContext;
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
            ->add(notification_block_render($GLOBALS['user']), 'notification');

        // Set default tab on dashboard
        if (current_path() == 'admin/dashboard') {
            $contextPane->setDefaultTab('notification');
        }
    }
}
