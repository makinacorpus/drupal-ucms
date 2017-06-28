<?php

namespace MakinaCorpus\Ucms\Label\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Calista\Action\Action;
use MakinaCorpus\Drupal\Calista\EventDispatcher\ContextPaneEvent;
use MakinaCorpus\Ucms\Label\LabelManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContextPaneEventSubscriber implements EventSubscriberInterface
{
    use StringTranslationTrait;

    /**
     * @var LabelManager
     */
    protected $manager;

    /**
     * Constructor
     * @param LabelManager $manager
     */
    public function __construct(LabelManager $manager)
    {
        $this->manager = $manager;
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

    public function onContextPaneInit(ContextPaneEvent $event)
    {
        if (
            (current_path() == 'admin/dashboard/label') &&
            ($this->manager->canEditNonLockedLabels() || $this->manager->canEditLockedLabels())
        ) {
            $action = new Action($this->t("Create label"), 'admin/dashboard/label/add', 'dialog', 'tags', 0, true, true);
            $event->getContextPane()->addActions([$action], null, 'tags', false);
        }
    }
}
