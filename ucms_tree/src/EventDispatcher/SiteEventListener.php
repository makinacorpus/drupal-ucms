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
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param SiteManager $siteManager
     */
    public function __construct(
        \DatabaseConnection $db,
        SiteManager $siteManager
    ) {
        $this->db = $db;
        $this->siteManager = $siteManager;
    }

    /**
     * @param DrupalMenuStorage $menuStorage
     */
    public function setMenuStorage($menuStorage)
    {
        $this->menuStorage = $menuStorage;
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
            if ($menuList) {
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
        $site = $event->getSite();

        // Create the site default menus
        if ($this->menuStorage) {
            $this->menuStorage->create(
                'site-main-'.$site->getId(),
                ['title' => $this->t("Main menu"), 'site_id' => $site->getId()]
            );
            $this->menuStorage->create(
                'site-footer-'.$site->getId(),
                ['title' => $this->t("Footer menu"), 'site_id' => $site->getId()]
            );
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

        // Duplicate menu links from menus.
        foreach ($this->menuStorage->loadWithConditions(['site_id' => $source->getId()]) as $menu) {
            $this
                ->db
                ->query(
                    "
                INSERT INTO {menu_links} (
                  menu_name, plid, link_path, link_title, router_path, `module`, options, weight, depth, has_children, 
                  p1, p2, p3, p4, p5, p6, p7, p8, p9, `external`
                )
                SELECT
                    :target, plid, link_path, link_title, router_path, `module`, options, weight, depth, has_children, 
                    p1, p2, p3, p4, p5, p6, p7, p8, p9, `external`
                FROM {menu_links}
                WHERE menu_name = :source
            ",
                    [
                        ':target' => str_replace($source->getId(), $target->getId(), $menu['name']),
                        ':source' => $menu['name'],
                    ]
                );
        }
    }
}
