<?php

namespace MakinaCorpus\Ucms\Dashboard\Page;

use Symfony\Component\HttpFoundation\Request;

class PageBuilder
{
    private $twig;
    private $defaultTemplate;

    /**
     * Default constructor
     *
     * @param \Twig_Environment $twig
     * @param string $defaultTemplate
     */
    public function __construct(\Twig_Environment $twig, $defaultTemplate)
    {
        $this->twig = $twig;
        $this->defaultTemplate = $defaultTemplate;
    }

    /**
     * Proceed to search and fetch state
     *
     * @param Request $request
     *   Incomming request
     * @param string[] $baseQuery
     *   Default non-changeable filters
     *
     * @return PageResult
     */
    public function search(DatasourceInterface $datasource, Request $request, array $baseQuery = [])
    {
        $route = $request->attributes->get('_route');
        $state = new PageState();

        $query = array_merge(
            $request->query->all(),
            $request->attributes->get('_route_params', []),
            $baseQuery
        );

        $query = Filter::fixQuery($query); // @todo this is ugly

        $datasource->init($query);

        $sort = new SortManager();
        $sort->prepare($route, $query);

        if ($sortFields = $datasource->getSortFields($query)) {
            $sort->setFields($sortFields);
            // Do not set the sort order links if there is no field to sort on
            if ($sortDefault = $datasource->getDefaultSort()) {
                $sort->setDefault(...$sortDefault);
            }
        }

        // Build the page state gracefully, this uglyfies the code but it does
        // help to reduce code within the datasources
        $state->setSortField($sort->getCurrentField($query));
        $state->setSortOrder($sort->getCurrentOrder($query));
        if (empty($query[$state->getPageParameter()])) {
            $state->setRange(24);
        } else {
            $state->setRange(24, $query[$state->getPageParameter()]);
        }
        // Same with search parameter and value
        $searchParameter = $datasource->getSearchFormParamName();
        $state->setCurrentSearch($searchParameter);
        if (!empty($query[$searchParameter])) {
            $state->setCurrentSearch($query[$searchParameter]);
        }

        $items = $datasource->getItems($query, $state);

        $filters = $datasource->getFilters($query);
        if ($filters) {
            foreach ($filters as $index => $filter) {
                if (isset($this->baseQuery[$filter->getField()])) {
                    unset($filters[$index]);
                }
                $filter->prepare($route, $query);
            }
        }

        return new PageResult($route, $state, $items, $query, $filters, $sort);
    }

    /**
     * Render the page using a template
     *
     * @param PageResult $result
     *   Page result from the search() method
     * @param array $arguments
     *   Additional arguments for the template, please note they will not
     *   override defaults
     * @param string $name
     *   Template name
     */
    public function render(PageResult $result, array $arguments = [], $name = null)
    {
        if (null === $name) {
            $name = $this->defaultTemplate;
        }

        $state = $result->getState();

        $arguments = [
            'state' => $state,
            'route' => $result->getRoute(),
            'filters' => $result->getFilters(),
            'display_mode_link' => [], // @todo
            'search_param' => false, // @todo
            'search_current' => false, // @todo
            'query' => $result->getQuery(),
            'sort' => $result->getSort(),
            'items' => $result->getItems(),
        ] + $arguments;

        return $this->twig->render($name, $arguments);
    }
}
