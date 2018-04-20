<?php

namespace MakinaCorpus\Ucms\Tree\EventDispatcher;

use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Umenu\Tree;
use MakinaCorpus\Umenu\TreeItem;

use Symfony\Component\EventDispatcher\Event;

final class MenuEvent extends Event
{
    const EVENT_TREE = 'menu:tree';

    private $menuName;
    private $items;
    private $deletedItems;

    /**
     * @var Site
     */
    private $site;

    public function __construct(string $menuName, Tree $tree, array $deletedItems = [], Site $site = null)
    {
        $this->menuName = $menuName;
        $this->tree = $tree;
        $this->deletedItems = $deletedItems;
        $this->site = $site;
    }

    public function hasSite() : bool
    {
        return $this->site instanceof Site;
    }

    /**
     * @return Site
     */
    public function getSite() : Site
    {
        return $this->site;
    }

    public function getMenuName() : string
    {
        return $this->menuName ?? '';
    }

    /**
     * @return Tree
     */
    public function getTree() : Tree
    {
        return $this->tree;
    }

    /**
     * @return TreeItem[]
     */
    public function getDeletedItems() : TreeItem
    {
        return $this->deletedItems;
    }
}
