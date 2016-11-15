<?php

namespace MakinaCorpus\Ucms\Tree\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\AbstractActionProvider;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Umenu\Menu;

class TreeActionProvider extends AbstractActionProvider
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
            $canUpdate = $this->isGranted($site, $this->account, Access::ACL_PERM_SITE_EDIT_TREE);
        }

        if ($canUpdate) {
            $ret[] = new Action($this->t("Tree"), 'admin/dashboard/tree/' . $item->getId(), [], 'th-list', -10, true, true);
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
