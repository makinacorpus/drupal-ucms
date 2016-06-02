<?php

namespace MakinaCorpus\Ucms\Tree\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;

use MakinaCorpus\Umenu\DrupalMenuStorage;
use Symfony\Component\EventDispatcher\GenericEvent;

class SiteEventListener
{
    use StringTranslationTrait;

    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * @var SiteManager
     */
    private $siteManager;

    /**
     * @var DrupalMenuStorage
     */
    private $menuStorage;

    /**
     * @var string
     */
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
     * @param DrupalMenuStorage $menuStorage
     */
    public function setMenuStorage($menuStorage)
    {
        $this->menuStorage = $menuStorage;
    }

    private function ensureSiteMenus(Site $site)
    {
        $ret = [];

        if ($this->menuStorage && $this->allowedMenus) {
            foreach ($this->allowedMenus as $prefix => $title) {
                $name = $prefix.'-'.$site->getId();
                if (!$this->menuStorage->exists($name)) {
                    $ret[$name] = $this->menuStorage->create(
                        $name,
                        [
                            'title'   => $this->t($title),
                            'site_id' => $site->getId(),
                        ]
                    );
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
    public function onSiteInit(SiteEvent $event)
    {
        $site = $event->getSite();

        // Reset menus.
        $activeMenus = [];
        if ($this->menuStorage) {

            $menuList = $this->menuStorage->loadWithConditions(['site_id' => $site->getId()]);

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
     * Recursion for onSiteClone()
     */
    private function recursiveMenuClone($name, $item, $parentId = null)
    {
        $new = [
            'menu_name'   => $name,
            'link_path'   => $item['link']['link_path'],
            'link_title'  => $item['link']['link_title'],
            'options'     => @unserialize($item['link']['options']),
            'weight'      => $item['link']['weight'],
        ];

        if ($parentId) {
            $new['plid'] = $parentId;
        }

        $mlid = menu_link_save($new);

        if (!empty($item['below'])) {
            foreach ($item['below'] as $child) {
                $this->recursiveMenuClone($name, $child, $mlid);
            }
        }
    }

    /**
     * On site cloning.
     *
     * @param GenericEvent $event
     */
    public function onSiteClone(GenericEvent $event)
    {
        /* @var Site */
        $source = $event->getArgument('source');
        /* @var Site */
        $target = $event->getSubject();

        // We do need them, they might not have been created.
        $this->ensureSiteMenus($target);

        $menuList = $this->menuStorage->loadWithConditions(['site_id' => $source->getId()]);
        foreach ($menuList as $menu) {

            $tree = _menu_build_tree($menu['name']);
            $name = str_replace($source->getId(), $target->getId(), $menu['name']);

            if ($tree && isset($tree['tree'])) {
                foreach ($tree['tree'] as $item) {
                    $this->recursiveMenuClone($name, $item);
                }
            }
        }
    }
}
