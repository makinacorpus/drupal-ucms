<?php

namespace MakinaCorpus\Ucms\Tree\EventDispatcher;

use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Umenu\Tree;
use MakinaCorpus\Umenu\TreeItem;

use Symfony\Component\EventDispatcher\Event;

class MenuEvent extends Event
{
    private $menuName;
    private $items;
    private $deletedItems;

    /**
     * @var Site
     */
    private $site;

    public function __construct($menuName, Tree $tree, $deletedItems = [], $site)
    {
        $this->menuName = $menuName;
        $this->tree = $tree;
        $this->deletedItems = $deletedItems;
        $this->site = $site;
    }

    public function hasSite()
    {
        return $this->site instanceof Site;
    }

    /**
     * @return Site
     */
    public function getSite()
    {
        return $this->site;
    }

    public function getMenuName()
    {
        return $this->menuName;
    }

    /**
     * @return Tree
     */
    public function getTree()
    {
        return $this->tree;
    }

    /**
     * @return TreeItem[]
     */
    public function getDeletedItems()
    {
        return $this->deletedItems;
    }
}
