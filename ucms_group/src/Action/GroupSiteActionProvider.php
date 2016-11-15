<?php

namespace MakinaCorpus\Ucms\Group\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\ACL\Permission;
use MakinaCorpus\Ucms\Dashboard\Action\AbstractActionProvider;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Group\GroupSite;
use MakinaCorpus\Ucms\Site\SiteManager;

/**
 * We only partially implement the site action provider, we do not want to
 * display irrelevant information in contextual actions
 */
class GroupSiteActionProvider extends AbstractActionProvider
{
    use StringTranslationTrait;

    private $siteManager;
    private $currentUser;

    /**
     * Default constructor
     *
     * @param SiteManager $siteManager
     * @param AccountInterface $currentUser
     */
    public function __construct(SiteManager $siteManager, AccountInterface $currentUser)
    {
        $this->siteManager = $siteManager;
        $this->currentUser = $currentUser;
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item)
    {
        $ret = [];

        /** @var \MakinaCorpus\Ucms\Group\GroupSite $item */
        $site = $item->getSite();

        if ($this->isGranted($site, $this->currentUser, Permission::OVERVIEW)) {
            $ret[] = new Action($this->t("View"), 'admin/dashboard/site/' . $site->getId(), null, 'eye-open', -10);
        }

        return $ret;
    }

    /**
     * {inheritdoc}
     */
    public function supports($item)
    {
        return $item instanceof GroupSite;
    }
}
