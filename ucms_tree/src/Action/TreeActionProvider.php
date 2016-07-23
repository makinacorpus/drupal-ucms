<?php

namespace MakinaCorpus\Ucms\Tree\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Umenu\Menu;

class TreeActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;

    private $siteManager;
    private $account;

    public function __construct(SiteManager $siteManager, AccountInterface $account)
    {
        $this->siteManager = $siteManager;
        $this->account = $account;
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item)
    {
        $ret = [];

        $canUpdate = false;

        /* @var $item Menu */

        if ($item->getSiteId()) {
            $site = $this->siteManager->getStorage()->findOne($item->getSiteId());
            $canUpdate = $this->siteManager->getAccess()->userCanEditTree($this->account, $site);
        }

        if ($canUpdate) {
            $ret[] = new Action($this->t("Edit"), 'admin/dashboard/tree/' . $item->getId() . '/edit', [], 'pencil', 0, true, true);
        }

        return $ret;
    }

    /**
     * {inheritdoc}
     */
    public function supports($item)
    {
        return $item instanceof Menu;
    }
}
