<?php

namespace MakinaCorpus\Ucms\Dashboard\Page;

class PageResult
{
    private $route;
    private $state;
    private $items;
    private $query;
    private $filters = [];
    private $sort;

    /**
     * Default constructor
     *
     * @param string $route
     * @param PageState $state
     * @param mixed[] $items
     * @param string[] $query
     * @param Filter[] $facets
     * @param SortManager $sort
     */
    public function __construct($route, PageState $state, array $items, array $query = [], array $filters = [], SortManager $sort = null)
    {
        $this->route = $route;
        $this->state = $state;
        $this->items = $items;
        $this->query = $query;
        $this->filters = $filters;
        $this->sort = $sort;
    }

    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @return PageState
     */
    public function getState()
    {
        return $this->state;
    }

    public function getItems()
    {
        return $this->items;
    }

    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return Filter[]
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @return SortManager
     */
    public function getSort()
    {
        return $this->sort;
    }
}
