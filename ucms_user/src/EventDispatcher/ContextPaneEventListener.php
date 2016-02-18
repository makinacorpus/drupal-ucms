<?php


namespace MakinaCorpus\Ucms\User\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\EventDispatcher\ContextPaneEvent;


class ContextPaneEventListener
{
    use StringTranslationTrait;


    public function onUcmsdashboardContextinit(ContextPaneEvent $event)
    {
        if (current_path() == 'admin/dashboard/user') {
            $action = new Action($this->t("Create user"), 'admin/dashboard/user/add', null, null, 0, true, true);
            $event->getContextPane()->addActions([$action]);
        }
    }
}

