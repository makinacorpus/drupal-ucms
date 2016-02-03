<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionRegistry;
use MakinaCorpus\Ucms\Dashboard\EventDispatcher\ContextPaneEvent;

class ContextPaneEventListener
{
    use StringTranslationTrait;

    /**
     * @var ActionRegistry
     */
    private $actionRegistry;

    /**
     * Default constructor
     *
     * @param ActionRegistry $actionRegistry
     */
    public function __construct(ActionRegistry $actionRegistry)
    {
        $this->actionRegistry = $actionRegistry;
    }

    public function onUcmsdashboardContextinit(ContextPaneEvent $event)
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