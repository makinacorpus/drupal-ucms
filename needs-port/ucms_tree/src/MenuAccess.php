<?php

namespace MakinaCorpus\Ucms\Tree;

use Drupal\Core\Session\AccountInterface;

use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Umenu\Menu;

/**
 * Menu access check helper
 */
class MenuAccess
{
    /**
     * Permission: manage all trees in the platform
     */
    const PERM_MANAGE_ALL_MENU = 'ucms tree menu admin';

    /**
     * @var SiteManager
     */
    private $siteManager;

    /**
     * Default constructor
     *
     * @param SiteManager $siteManager
     */
    public function __construct(SiteManager $siteManager)
    {
        $this->siteManager = $siteManager;
    }

    private function ensureSiteContext(Site $site = null)
    {
        if (!$site && $this->siteManager->hasContext()) {
            $site = $this->siteManager->getContext();
        }

        return $site;
    }

    /**
     * Is user webmaster in the menu site
     *
     * @param Menu $menu
     * @param AccountInterface $account
     *
     * @return bool
     */
    public function isUserWebmaster(Menu $menu, AccountInterface $account)
    {
        if ($menu->hasSiteId()) {
            $site = $this->siteManager->getStorage()->findOne($menu->getSiteId());

            return $this->siteManager->getAccess()->userIsWebmaster($account, $site);
        }

        return false;
    }

    /**
     * Can user edit the given menu
     *
     * @param Menu $menu
     * @param AccountInterface $account
     *
     * @return bool
     */
    public function canEditMenu(Menu $menu, AccountInterface $account)
    {
        return $account->hasPermission(self::PERM_MANAGE_ALL_MENU) || $this->isUserWebmaster($menu, $account);
    }

    /**
     * Can user edit the given menu children
     *
     * @param Menu $menu
     * @param AccountInterface $account
     *
     * @return bool
     */
    public function canEditTree(Menu $menu, AccountInterface $account)
    {
        return $this->canEditMenu($menu, $account);
    }

    /**
     * Can user delete the given menu
     *
     * @param Menu $menu
     * @param AccountInterface $account
     *
     * @return bool
     */
    public function canDeleteMenu(Menu $menu, AccountInterface $account)
    {
        if ($menu->isSiteMain()) {
            return false;
        }

        return $account->hasPermission(self::PERM_MANAGE_ALL_MENU) || $this->isUserWebmaster($menu, $account);
    }

    /**
     * Can user access to the menu admin in the current context
     *
     * @param AccountInterface $account
     * @param Site $site
     *
     * @return bool
     */
    public function canAccessMenuAdmin(AccountInterface $account, Site $site = null)
    {
        if ($account->hasPermission(self::PERM_MANAGE_ALL_MENU)) {
            return true;
        }

        $site = $this->ensureSiteContext($site);

        if ($site) {
            return $this->siteManager->getAccess()->userIsWebmaster($account, $site);
        } else {
            return $this->siteManager->getAccess()->userIsWebmaster($account);
        }

        return  false;
    }

    /**
     * Can user create a menu in the given context
     *
     * @param AccountInterface $account
     * @param Site $site
     *
     * @return bool
     */
    public function canCreateMenu(AccountInterface $account, Site $site = null)
    {
        $site = $this->ensureSiteContext($site);

        return $account->hasPermission(self::PERM_MANAGE_ALL_MENU) || $this->siteManager->getAccess()->userIsWebmaster($account, $site);
    }
}
