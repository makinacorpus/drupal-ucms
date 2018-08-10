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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use MakinaCorpus\Ucms\Site\NodeManager;

class SiteEventSubscriber implements EventSubscriberInterface
{
    use StringTranslationTrait;

    private $allowedMenus = [];
    private $database;
    private $nodeManager;
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
            TreeEvent::EVENT_TREE => [
                ['onTreeUpdate', 0],
            ]
        ];
    }

    /**
     * Default constructor
     */
    public function __construct(Connection $database, SiteManager $siteManager, TreeManager $treeManager, NodeManager $nodeManager, array $allowedMenus)
    {
        $this->allowedMenus = $allowedMenus;
        $this->database = $database;
        $this->nodeManager = $nodeManager;
        $this->siteManager = $siteManager;
        $this->treeManager = $treeManager;
    }

    /**
     * Compute menu identifier
     */
    public static function getMenuName(Site $site, string $prefix = 'main'): string
    {
        return 'site-'.$prefix.'-'.$site->getId();
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
                $name = self::getMenuName($site, $prefix);
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
                $sourceName = self::getMenuName($source, $prefix);
                $targetName = self::getMenuName($target, $prefix);
                $this->treeManager->cloneMenuIn($sourceName, $targetName);
            }
        }
    }

    /**
     * On tree update set correct node references
     *
     * @todo this should live into the 'ucms_site' module, for this:
     *   - umenu should provide the transactionnal wrapper for menu tree save
     *   - umenu should raise itself the event (and not ucms_tree)
     */
    public function onTreeUpdate(TreeEvent $event)
    {
        // @todo should we remove site reference for nodes that have been removed from tree?
        // FIXME: NOT SCALABLE
        //   - should be implemented when items are added into tree, not moved
        //   - keeping this as of now, but it must be done otherwise
        $menu = $event->getMenu();
        if ($siteId = $menu->getSiteId()) {
            $nodeIdList = $this->database->query("SELECT node_id FROM {umenu_item} WHERE menu_id = ?", [$menu->getId()])->fetchCol();
            if ($nodeIdList) {
                // @todo references should drop node-related caches:
                //   - tags of those node
                //   - tags node_list
                //   - page and site context?
                $this->nodeManager->createReferenceBulkInSite($siteId, $nodeIdList);
            }
        }
    }
}
