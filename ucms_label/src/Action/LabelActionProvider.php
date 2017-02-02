<?php

namespace MakinaCorpus\Ucms\Label\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Drupal\Dashboard\Action\Action;
use MakinaCorpus\Drupal\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Label\LabelManager;

class LabelActionProvider implements ActionProviderInterface
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
     */
    public function __construct(LabelManager $manager, AccountInterface $currentUser)
    {
        $this->manager = $manager;
        $this->currentUser = $currentUser;
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item)
    {
        $actions = [];

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
        return isset($item->vocabulary_machine_name) && ($item->vocabulary_machine_name === $this->manager->getVocabularyMachineName());
    }
}
