<?php

namespace MakinaCorpus\Ucms\Dashboard\Page;

/**
 * This will be used on items that will get to the templates, in order for the
 * graphic integrator to avoid having to use the 'route' and 'query' parameters
 * by himself.
 */
trait PrepareableTrait
{
    private $prepared = false;
    private $route;
    private $query = [];

    /**
     * Set route
     *
     * @param string $route
     *   Current route
     * @param string[] $query
     *   Current incomming query
     */
    public function prepare($route, array $query = [])
    {
        $this->route = $route;
        $this->query = $query;
        $this->prepared = true;
    }

    /**
     * Get route name
     *
     * @return string
     */
    public function getRoute()
    {
        if (!$this->prepared) {
            throw new \LogicException("You must call ::prepare() before using me");
        }

        return $this->route;
    }

    /**
     * Get route parameters
     *
     * @return string[)
     */
    public function getQuery()
    {
        if (!$this->prepared) {
            throw new \LogicException("You must call ::prepare() before using me");
        }

        return $this->query;
    }

    /**
     * Get route parameters (alias of ::getQuery())
     *
     * @return string[)
     */
    public function getRouteParamaters()
    {
        return $this->getQuery();
    }
}

