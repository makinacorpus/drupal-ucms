<?php

namespace MakinaCorpus\Ucms\Tree\EventDispatcher;

use Symfony\Component\EventDispatcher\Event;

class MenuEvent extends Event
{
    private $menuName;
    private $rootItems;
    private $deletedItems;

    public function __construct($menuName, $rootItems = [], $deletedItems = [])
    {
        $this->menuName = $menuName;
        $this->rootItems = $rootItems;
        $this->deletedItems = $deletedItems;
    }

    public function getMenuName()
    {
        return $this->menuName;
    }

    public function getRootItems()
    {
        return $this->rootItems;
    }

    public function getDeletedItems()
    {
        return $this->deletedItems;
    }
}
