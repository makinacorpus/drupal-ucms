<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Calista\Action\Action;
use MakinaCorpus\Calista\Action\ActionRegistry;
use MakinaCorpus\Drupal\Calista\EventDispatcher\ContextPaneEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContextPaneEventSubscriber implements EventSubscriberInterface
{
    use StringTranslationTrait;

    private $actionRegistry;

    public function __construct(ActionRegistry $actionRegistry)
    {
        $this->actionRegistry = $actionRegistry;
    }

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            ContextPaneEvent::EVENT_INIT => [
                ['onContextPaneInit', 0],
            ],
        ];
    }

    public function onContextPaneInit(ContextPaneEvent $event)
    {
        if ('admin/dashboard/site' === current_path()) {
            $event->getContextPane()->addActions([
                new Action($this->t("Request site"), 'admin/dashboard/site/request', null, 'globe', 0, true, true),
            ]);
        } else if ('admin/dashboard/site/' === substr(current_path(), 0, 21)) {
            if ($site = menu_get_object('ucms_site', 3)) {
                $event->getContextPane()->addActions(
                    $this->actionRegistry->getActions($site)
                );
            }
        }
    }

}