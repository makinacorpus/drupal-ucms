<?php


namespace MakinaCorpus\Ucms\Label\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\EventDispatcher\ContextPaneEvent;
use MakinaCorpus\Ucms\Label\LabelManager;


class ContextPaneEventListener
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


    public function onUcmsdashboardContextinit(ContextPaneEvent $event)
    {
        if (
            (current_path() == 'admin/dashboard/label') &&
            ($this->manager->canEditNonLockedLabels() || $this->manager->canEditLockedLabels())
        ) {
            $action = new Action($this->t("Create label"), 'admin/dashboard/label/add', 'dialog', null, 0, true, true);
            $event->getContextPane()->addActions([$action]);
        }
    }
}

