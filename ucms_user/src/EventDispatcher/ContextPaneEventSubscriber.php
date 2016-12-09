<?php

namespace MakinaCorpus\Ucms\User\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\EventDispatcher\ContextPaneEvent;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContextPaneEventSubscriber implements EventSubscriberInterface
{
    use StringTranslationTrait;

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            ContextPaneEvent::EVENT_INIT => [
                ['onUcmsdashboardContextinit', 0],
            ],
        ];
    }

    public function onUcmsdashboardContextinit(ContextPaneEvent $event)
    {
        if (current_path() == 'admin/dashboard/user') {
            $action = new Action($this->t("Create user"), 'admin/dashboard/user/add', null, 'user', 0, true, true);
            $event->getContextPane()->addActions([$action]);
        }
    }
}
