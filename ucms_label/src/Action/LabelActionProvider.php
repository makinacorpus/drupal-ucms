<?php


namespace MakinaCorpus\Ucms\Label\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Label\LabelAccess;
use MakinaCorpus\Ucms\Label\LabelManager;
use MakinaCorpus\Ucms\Notification\NotificationService;


class LabelActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;


    /**
     * @var SiteManager
     */
    private $manager;

    /**
     * @var NotificationService
     */
    private $notifService;

    /**
     * @var AccountInterface
     */
    private $currentUser;


    /**
     * Default constructor
     *
     * @param LabelManager $manager
     */
    public function __construct(LabelManager $manager, NotificationService $notifService, AccountInterface $currentUser)
    {
        $this->manager = $manager;
        $this->notifService = $notifService;
        $this->currentUser = $currentUser;
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

        if ($this->manager->canEditLabel($item)) {
            $actions[] = new Action($this->t("Edit"), 'admin/dashboard/label/' . $item->tid . '/edit', 'dialog', 'pencil', -20, true, true);
            $actions[] = new Action($this->t("Delete"), 'admin/dashboard/label/' . $item->tid . '/delete', 'dialog', 'trash', -10, true, true, $this->manager->hasChildren($item));
        }

        return $actions;
    }


    /**
     * {inheritdoc}
     */
    public function supports($item)
    {
        return (
            isset($item->vocabulary_machine_name) &&
            ($item->vocabulary_machine_name === $this->manager->getVocabularyMachineName())
        );
    }
}

