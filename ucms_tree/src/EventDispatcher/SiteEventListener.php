<?php

namespace MakinaCorpus\Ucms\Tree\EventDispatcher;

use Drupal\Core\Database\Connection;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteCloneEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvents;
use MakinaCorpus\Umenu\TreeManager;

class SiteEventListener
{
    use StringTranslationTrait;

    private $allowedMenus = [];
    private $database;
    private $siteManager;
    private $treeManager;

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            SiteEvents::EVENT_CREATE => [
                ['onSiteCreate', 0],
            ],
            SiteEvents::EVENT_CLONE => [
                ['onInsertClone', 0],
            ],
        ];
    }

    /**
     * Default constructor
     */
    public function __construct(Connection $database, SiteManager $siteManager, TreeManager $treeManager, array $allowedMenus)
    {
        $this->allowedMenus = $allowedMenus;
        $this->database = $database;
        $this->siteManager = $siteManager;
        $this->treeManager = $treeManager;
    }

    /**
     * Compute menu identifier
     */
    private function getMenuName(Site $site, string $prefix = 'main'): string
    {
        return $prefix.'-'.$site->getId();
    }

    /**
     * Create missing menus for site and return the created menu list
     */
    private function ensureSiteMenus(Site $site): array
    {
        $ret = [];
        if ($this->allowedMenus) {
            $storage = $this->treeManager->getMenuStorage();
            foreach ($this->allowedMenus as $prefix => $title) {
                $name = $this->getMenuName($site, $prefix);
                if (!$storage->exists($name)) {
                    $ret[$name] = $storage->create($name, ['title' => $this->t($title), 'site_id' => $site->getId()]);
                }
            }
        }
        return $ret;
    }

    /**
     * On site creation ensure menus.
     */
    public function onSiteCreate(SiteEvent $event)
    {
        $this->ensureSiteMenus($event->getSite());
    }

    /**
     * On site clone duplicate menus
     */
    public function onSiteClone(SiteCloneEvent $event)
    {
        $source = $event->getTemplateSite();
        $target = $event->getSite();

        $this->ensureSiteMenus($source);
        $this->ensureSiteMenus($target);

        if ($this->allowedMenus) {
            foreach (\array_keys($this->allowedMenus) as $prefix) {
                $sourceName = $this->getMenuName($source, $prefix);
                $targetName = $this->getMenuName($target, $prefix);
                $this->treeManager->cloneMenuIn($sourceName, $targetName);
            }
        }
    }
}
