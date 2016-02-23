<?php

namespace MakinaCorpus\Ucms\Site\Action;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Site\NodeAccessHelper;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;

/**
 * The site module will add node actions, corresponding to reference
 * and cloning operations
 */
class NodeActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;

    /**
     * @var SiteManager
     */
    private $manager;

    /**
     * @var NodeAccessHelper
     */
    private $nodeAccess;

    /**
     * Default constructor
     *
     * @param SiteManager $manager
     */
    public function __construct(SiteManager $manager, NodeAccessHelper $nodeAccess)
    {
        $this->manager = $manager;
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

        if ($this->manager->getAccess()->userCanReference($item, $account->id())) {
            $ret[] = new Action($this->t("Reference it on my site"), 'node/' . $item->nid . '/reference', 'dialog', 'download-alt', 2, true, true);
        }
        if ($this->nodeAccess->canUserLock($item, $account)) {
            if ($item->is_clonable) {
                $ret[] = new Action($this->t("Lock"), 'node/' . $item->nid . '/lock', 'dialog', 'lock', 2, false, true);
            } else {
                $ret[] = new Action($this->t("Unlock"), 'node/' . $item->nid . '/unlock', 'dialog', 'lock', 2, false, true);
            }
        }

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
