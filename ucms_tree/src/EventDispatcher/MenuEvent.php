<?php

namespace MakinaCorpus\Ucms\Tree\EventDispatcher;

use MakinaCorpus\Ucms\Site\Site;

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

    public function __construct($menuName, $items = [], $deletedItems = [], $site)
    {
        $this->menuName = $menuName;
        $this->items = $items;
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

    public function getRootItems()
    {
        return array_filter($this->items, function ($item) {
            return empty($item['plid']);
        });
    }

    public function getAllItems()
    {
        return $this->items;
    }

    public function getDeletedItems()
    {
        return $this->deletedItems;
    }
}
