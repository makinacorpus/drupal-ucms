<?php

namespace MakinaCorpus\Ucms\Taxo\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Site\Access;

/**
 * @todo rewrite all of this in master
 */
class TermActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;

    private $currentUser;

    /**
     * Default constructor
     */
    public function __construct(AccountInterface $currentUser)
    {
        $this->currentUser = $currentUser;
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item)
    {
        $ret = [];

        if (ucms_taxo_term_access($item, Access::OP_UPDATE)) {
            $ret[] = new Action($this->t("Edit"), 'admin/dashboard/taxonomy/'.$item->vocabulary_machine_name.'/'.$item->tid.'/update', 'dialog', 'pencil');
        }
        if (ucms_taxo_term_access($item, Access::OP_DELETE)) {
            $ret[] = new Action($this->t("Delete"), 'admin/dashboard/taxonomy/'.$item->vocabulary_machine_name.'/'.$item->tid.'/delete', 'dialog', 'trash');
        }

        return $ret;
    }

    /**
     * {inheritdoc}
     */
    public function supports($item)
    {
        return isset($item->vid) && isset($item->tid) && isset($item->vocabulary_machine_name);
    }
}
