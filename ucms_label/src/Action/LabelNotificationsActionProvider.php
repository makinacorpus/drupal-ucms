<?php

namespace MakinaCorpus\Ucms\Label\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Label\LabelManager;
use MakinaCorpus\Ucms\Notification\NotificationService;

class LabelNotificationsActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;

    private $manager;
    private $notifService;
    private $currentUser;

    /**
     * Default constructor
     *
     * @param LabelManager $manager
     * @param AccountInterface $currentUser
     * @param NotificationService $notifService
     */
    public function __construct(LabelManager $manager, AccountInterface $currentUser, NotificationService $notifService)
    {
        $this->manager = $manager;
        $this->currentUser = $currentUser;
        $this->notifService = $notifService;
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item)
    {
        $actions = [];

        if (!$this->manager->isRootLabel($item)) {
            if (!$this->notifService->isSubscribedTo($this->currentUser->id(), 'label:' . $item->tid)) {
                $actions[] = new Action($this->t("Subscribe to the notifications"), 'admin/dashboard/label/' . $item->tid . '/subscribe', 'dialog', 'bell', -30, true, true);
            } else {
                $actions[] = new Action($this->t("Unsubscribe from the notifications"), 'admin/dashboard/label/' . $item->tid . '/unsubscribe', 'dialog', 'remove', -30, true, true);
            }
        }

        return $actions;
    }

    /**
     * {inheritdoc}
     */
    public function supports($item)
    {
        return isset($item->vocabulary_machine_name) && ($item->vocabulary_machine_name === $this->manager->getVocabularyMachineName());
    }
}
