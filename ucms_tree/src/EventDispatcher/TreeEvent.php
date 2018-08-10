<?php

namespace MakinaCorpus\Ucms\Tree\EventDispatcher;

use MakinaCorpus\Umenu\Menu;
use Symfony\Component\EventDispatcher\Event;

final class TreeEvent extends Event
{
    const EVENT_TREE = 'ucms_tree:tree';

    private $deletedItems;
    private $menu;

    public function __construct(Menu $menu, array $deletedItems = [])
    {
        $this->deletedItems = $deletedItems;
        $this->menu = $menu;
    }

    public function getMenu(): Menu
    {
        return $this->menu;
    }

    public function getDeletedItems(): array
    {
        return $this->deletedItems;
    }
}
