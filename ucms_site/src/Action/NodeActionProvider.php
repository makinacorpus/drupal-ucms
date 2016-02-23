<?php

namespace MakinaCorpus\Ucms\Site\Action;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Site\NodeAccessService;
use MakinaCorpus\Ucms\Site\Site;

/**
 * The site module will add node actions, corresponding to reference
 * and cloning operations
 */
class NodeActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;

    /**
     * @var NodeAccessService
     */
    private $nodeAccess;

    /**
     * Default constructor
     *
     * @param NodeAccessService $nodeAccess
     */
    public function __construct(NodeAccessService $nodeAccess)
    {
        $this->nodeAccess = $nodeAccess;
    }

    /**
     * @return AccountInterface
     */
    private function getCurrentAccount()
    {
        return $GLOBALS['user'];
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item)
    {
        $ret = [];

        /* @var $item NodeInterface */
        $account = $this->getCurrentAccount();

        if ($this->nodeAccess->canUserReference($item, $account)) {
            $ret[] = new Action($this->t("Reference it on my site"), 'node/' . $item->nid . '/reference', 'dialog', 'download-alt', 2, true, true);
        }
        if ($this->nodeAccess->canUserLock($item, $account)) {
            if ($item->is_clonable) {
                $ret[] = new Action($this->t("Lock"), 'node/' . $item->nid . '/lock', 'dialog', 'lock', 2, false, true);
            } else {
                $ret[] = new Action($this->t("Unlock"), 'node/' . $item->nid . '/unlock', 'dialog', 'lock', 2, false, true);
            }
        }

        /*
         if ($item->access('clone')) {
         $ret[] = new Action(t("Clone"), 'node/' . $item->nid . '/clone', null, 'dialog', 'save', 0, false, true);
         }
         if (!empty($item->is_clonable)) {
         // ajouter au panier  permet d'ajouter le contenu au panier de l'utilisateur courant ;
         // enlever du panier  permet d'enlever le contenu du panier de l'utilisateur courant ;
         }
         */

        return $ret;
    }

    /**
     * {inheritdoc}
     */
    public function supports($item)
    {
        return $item instanceof NodeInterface;
    }
}
