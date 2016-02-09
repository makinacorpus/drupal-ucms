<?php


namespace MakinaCorpus\Ucms\Label\Action;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Label\LabelAccess;
use MakinaCorpus\Ucms\Label\LabelManager;


class LabelActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;


    /**
     * @var SiteManager
     */
    private $manager;


    /**
     * Default constructor
     *
     * @param LabelManager $manager
     */
    public function __construct(LabelManager $manager)
    {
        $this->manager = $manager;
    }


    /**
     * {inheritdoc}
     */
    public function getActions($item)
    {
        $actions = [];

        if (($item->is_locked == 0 && user_access(LabelAccess::PERM_EDIT_NON_LOCKED)) ||
            ($item->is_locked == 1 && user_access(LabelAccess::PERM_EDIT_LOCKED))
        ) {
            $actions[] = new Action($this->t("Edit"), 'admin/dashboard/label/' . $item->tid . '/edit', null, 'pencil', -15, true, true);
            //$actions[] = new Action($this->t("History"), 'admin/dashboard/site/' . $item->tid . '/log', null, 'list-alt', -10, false);
            //$actions[] = new Action($this->t("Delete"), 'admin/dashboard/label/' . $item->tid . '/delete', null, 'trash', -5, true, true);
        }

        return $actions;
    }


    /**
     * {inheritdoc}
     */
    public function supports($item)
    {
        return ($item->vocabulary_machine_name === $this->manager->getVocabularyMachineName());
    }
}

