<?php

namespace MakinaCorpus\Ucms\Tree\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteCloneEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteInitEvent;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Umenu\TreeManager;

class SiteEventListener
{
    use StringTranslationTrait;

    private $db;
    private $siteManager;
    private $treeManager;
    private $allowedMenus = [];

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param SiteManager $siteManager
     * @param string[] $allowedMenus
     *   Keys are menu name prefix, values are human readable english names
     */
    public function __construct(
        \DatabaseConnection $db,
        SiteManager $siteManager,
        $allowedMenus
    ) {
        $this->db = $db;
        $this->siteManager = $siteManager;
        $this->allowedMenus = $allowedMenus;
    }

    /**
     * @param TreeManager $menuStorage
     */
    public function setTreeManager(TreeManager $treeManager)
    {
        $this->treeManager = $treeManager;
    }

    /**
     * Create missing menus for site
     *
     * @param Site $site
     *
     * @return string[][]
     *   Newly created menus
     */
    private function ensureSiteMenus(Site $site)
    {
        $ret = [];

        if ($this->treeManager && $this->allowedMenus) {
            $storage = $this->treeManager->getMenuStorage();

            foreach ($this->allowedMenus as $prefix => $title) {

                $name = $prefix.'-'.$site->getId();

                if (!$storage->exists($name)) {
                    $ret[$name] = $storage->create($name, ['title' => $this->t($title), 'site_id' => $site->getId()]);
                }
            }
        }

        return $ret;
    }

    /**
     * On site context initialization.
     *
     * @param SiteEvent $event
     */
    public function onSiteInit(SiteInitEvent $event)
    {
        $site = $event->getSite();

        // Reset menus.
        $activeMenus = [];
        if ($this->treeManager) {

            $menuList = $this->treeManager->getMenuStorage()->loadWithConditions(['site_id' => $site->getId()]);

            if (empty($menuList)) {
                $menuList = $this->ensureSiteMenus($event->getSite());
            }

            // @todo
            //   pri: keeping this code in case it happens again, on my env
            //   all menus have been droppped for an obscure reason...
            if (false && $menuList) {
                foreach ($menuList as $menu) {
                    $activeMenus[] = $menu['name'];
                }
            }
        }
        $activeMenus[] = 'navigation';
        $GLOBALS['conf']['menu_default_active_menus'] = $activeMenus;
    }

    /**
     * On site creation.
     *
     * @param SiteEvent $event
     */
    public function onSiteCreate(SiteEvent $event)
    {
        $this->ensureSiteMenus($event->getSite());
    }

    /**
     * On site cloning.
     *
     * @param SiteCloneEvent $event
     */
    public function onSiteClone(SiteCloneEvent $event)
    {
        $source = $event->getTemplateSite();
        $target = $event->getSite();

        $this->ensureSiteMenus($source);
        $this->ensureSiteMenus($target);

        if ($this->treeManager && $this->allowedMenus) {
            foreach (array_keys($this->allowedMenus) as $prefix) {

                $sourceName = $prefix . '-' . $source->getId();
                $targetName = $prefix . '-' . $target->getId();

                $this->treeManager->cloneMenuIn($sourceName, $targetName);
            }
        }
    }
}
