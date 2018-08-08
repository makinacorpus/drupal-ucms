<?php

namespace MakinaCorpus\Ucms\Tree\EventDispatcher;

use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Umenu\Tree;
use MakinaCorpus\Umenu\TreeItem;
use Symfony\Component\EventDispatcher\Event;

final class MenuEvent extends Event
{
    const EVENT_TREE = 'menu:tree';

    private $deletedItems;
    private $items;
    private $menuName;
    private $site;

    public function __construct(string $menuName, Tree $tree, array $deletedItems = [], Site $site = null)
    {
        $this->deletedItems = $deletedItems;
        $this->menuName = $menuName;
        $this->site = $site;
        $this->tree = $tree;
    }

    public function hasSite() : bool
    {
        return $this->site instanceof Site;
    }

    public function getSite() : Site
    {
        if (!$this->site) {
            throw new \LogicException("No site set");
        }

        return $this->site;
    }

    public function getMenuName() : string
    {
        return $this->menuName ?? '';
    }

    public function getTree() : Tree
    {
        return $this->tree;
    }

    public function getDeletedItems() : TreeItem
    {
        return $this->deletedItems;
    }
}
