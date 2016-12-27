<?php

namespace MakinaCorpus\Ucms\Dashboard\Page;

use Symfony\Component\HttpFoundation\Request;

class PageBuilder
{
    private $twig;
    private $debug = false;
    private $defaultDisplay = 'table';
    private $templates = [];

    /**
     * Default constructor
     *
     * @param \Twig_Environment $twig
     * @param string[] $templates
     * @param string $defaultDisplay
     *   Default template
     */
    public function __construct(\Twig_Environment $twig, array $templates = [], $defaultDisplay = 'table')
    {
        $this->twig = $twig;
        $this->debug = $twig->isDebug();
        $this->defaultDisplay = $defaultDisplay;
        $this->templates = $templates;
    }

    /**
     * Get default template
     *
     * @return string
     */
    private function getDefaultTemplate()
    {
        if (empty($this->templates)) {
            throw new \InvalidArgumentException("page builder has no templates");
        }

        if (isset($this->templates[$this->defaultDisplay])) {
            return $this->templates[$this->defaultDisplay];
        }

        if ($this->debug) {
            trigger_error("page builder has no explicit 'default' template set, using first in array", E_USER_WARNING);
        }

        return reset($this->templates);
    }

    /**
     * Get template for given display name
     *
     * @param string $displayName
     *
     * @return string
     */
    private function getTemplateFor($displayName = null, $fallback = null)
    {
        if (empty($displayName)) {
            return $this->getDefaultTemplate();
        }

        if (!isset($this->templates[$displayName])) {
            if ($this->debug) {
                trigger_error(sprintf("%s: display has no associated template, switching to default", $displayName), E_USER_WARNING);
            }

            if ($fallback) {
                return $this->getTemplateFor($fallback);
            }

            return $this->getDefaultTemplate();
        }

        return $this->templates[$displayName];
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
        $state->setSearchParameter($searchParameter);
        $state->setCurrentSearch($request->get($searchParameter));
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

        // Set current display
        $state->setCurrentDisplay($request->get('display'));

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
     * @param string $defaultDisplay
     *   Default display name if a specific one suits more for the content
     */
    public function render(PageResult $result, array $arguments = [], $defaultDisplay = null)
    {
        $state = $result->getState();

        $display = $state->getCurrentDisplay();
        if (!$display) {
            if ($defaultDisplay) {
                $state->setCurrentDisplay($display = $defaultDisplay);
            } else {
                $state->setCurrentDisplay($display = $this->defaultDisplay);
            }
        }

        // Build display links
        // @todo Do it better...
        $displayLinks = [];
        foreach (array_keys($this->templates) as $name) {
            switch ($name) {
                case 'grid':
                    $displayIcon = 'th';
                    break;
                default:
                case 'table':
                    $displayIcon = 'th-list';
                    break;
            }
            $displayLinks[] = new Link($name, $result->getRoute(), ['display' => $name] + $result->getQuery(), $display === $name, $displayIcon);
        }

        $arguments = [
            'state'     => $state,
            'route'     => $result->getRoute(),
            'filters'   => $result->getFilters(),
            'displays'  => $displayLinks,
            'query'     => $result->getQuery(),
            'sort'      => $result->getSort(),
            'items'     => $result->getItems(),
        ] + $arguments;

        return $this
            ->twig
            ->load($this->getTemplateFor($display))
            ->renderBlock('page', $arguments)
        ;
    }

    /**
     * Shortcut for controllers
     *
     * @param DatasourceInterface $datasource
     * @param Request $request
     * @param array $baseQuery
     * @param unknown $defaultDisplay
     *
     * @return string
     */
    public function searchAndRender(DatasourceInterface $datasource, Request $request, array $baseQuery = [], $defaultDisplay = null)
    {
        return $this->render($this->search($datasource, $request, $baseQuery), [], $defaultDisplay);
    }
}
