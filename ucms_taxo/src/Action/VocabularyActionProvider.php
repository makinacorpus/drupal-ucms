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
class VocabularyActionProvider implements ActionProviderInterface
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

        if (ucms_taxo_vocabulary_access($item, Access::OP_VIEW)) {
            $ret[] = new Action($this->t("View"), 'admin/dashboard/taxonomy/' . $item->machine_name, [], 'eye-open');
        }

        return $ret;
    }

    /**
     * {inheritdoc}
     */
    public function supports($item)
    {
        return isset($item->vid) && isset($item->machine_name) && isset($item->hierarchy);
    }
}
