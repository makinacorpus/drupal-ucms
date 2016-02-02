<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\EventDispatcher\ContextPaneEvent;

class ContextPaneEventListener
{
    use StringTranslationTrait;

    public function onUcmsdashboardContextinit(ContextPaneEvent $event)
    {
        if ('admin/dashboard/site' === current_path()) {
            $event->getContextPane()->addActions([
                new Action($this->t("Request site"), 'admin/dashboard/site/request', null, 'globe', 0, true, true),
            ]);
        }
    }

}
