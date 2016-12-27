<?php

namespace MakinaCorpus\Ucms\Dashboard\Page;

use Symfony\Component\HttpFoundation\Request;

/**
 * @todo
 *   - Remove buiseness methods from this oibject and move them to "Page"
 *   - widget factory should return a page, not a builder
 */
final class PageBuilder
{
    private $id;
    private $twig;
    private $debug = false;
    private $defaultDisplay = 'table';
    private $datasource;
    private $templates = [];
    private $baseQuery = [];

    /**
     * Default constructor
     *
     * @param \Twig_Environment $twig
     * @param string[] $displays
     * @param string $defaultDisplay
     *   Default template
     */
    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;
        $this->debug = $twig->isDebug();
    }

    /**
     * Set builder identifier
     *
     * @param string $id
     */
    public function setId($id)
    {
        if ($this->id && $this->id !== $id) {
            throw new \LogicException("cannot change a page builder identifier");
        }

        $this->id = $id;
    }

    /**
     * Set datasource
     *
     * @param DatasourceInterface $datasource
     *
     * @return $this
     */
    public function setDatasource(DatasourceInterface $datasource)
    {
        $this->datasource = $datasource;

        return $this;
    }

    /**
     * Get datasource
     *
     * @return DatasourceInterface
     */
    public function getDatasource()
    {
        if (!$this->datasource) {
            throw new \LogicException("cannot build page without a datasource");
        }

        return $this->datasource;
    }

    /**
     * Set default display
     *
     * @param string $defaultDisplay
     *   Display identifier
     *
     * @return $this
     */
    public function setDefaultDisplay($display)
    {
        $this->defaultDisplay = $display;

        return $this;
    }

    /**
     * Set allowed templates
     *
     * @param string[] $displays
     *
     * @return $this
     */
    public function setAllowedTemplates(array $displays)
    {
        $this->templates = $displays;

        return $this;
    }

    /**
     * Set base query
     *
     * @param array $query
     *
     * @return $this
     */
    public function setBaseQuery(array $query)
    {
        $this->baseQuery = $query;

        return $this;
    }

    /**
     * Add base query parameter
     *
     * @param string $name
     * @param mixed $value
     *
     * @return $this
     */
    public function addBaseQueryParameter($name, $value)
    {
        $this->baseQuery[$name] = $value;

        return $this;
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

    private function computeId()
    {
        if (!$this->id) {
            return null;
        }

        // @todo do better than that...
        return $this->id;
    }

    /**
     * Proceed to search and fetch state
     *
     * @param Request $request
     *   Incomming request
     *
     * @return PageResult
     */
    public function search(Request $request)
    {
        $datasource = $this->getDatasource();

        $route = $request->attributes->get('_route');
        $state = new PageState();

        $query = array_merge(
            $request->query->all(),
            $request->attributes->get('_route_params', [])
        );

        $query = Filter::fixQuery($query); // @todo this is ugly

        // Check that there is no value out of bounds of the filter query to
        // ensure we do override the incomming request query parameters, and
        // avoid security issues
        if ($this->baseQuery) {
            foreach ($this->baseQuery as $name => $value) {
                if (isset($query[$name])) {
                    // @todo end this...
                }
            }
        }

        $datasource->init($query, $this->baseQuery);

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
        } else { // Avoid possibly broken implementations
            $filters = [];
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
     *
     * @return PageView
     */
    public function render(PageResult $result, array $arguments = [])
    {
        $state = $result->getState();

        $display = $state->getCurrentDisplay();
        if (!$display) {
            $state->setCurrentDisplay($display = $this->defaultDisplay);
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
            'uuid'      => $this->computeId(),
            'state'     => $state,
            'route'     => $result->getRoute(),
            'filters'   => $result->getFilters(),
            'display'   => $display,
            'displays'  => $displayLinks,
            'query'     => $result->getQuery(),
            'sort'      => $result->getSort(),
            'items'     => $result->getItems(),
        ] + $arguments;

        return new PageView($this->twig, $this->getTemplateFor($arguments['display']), $arguments);
    }

    /**
     * Shortcut for controllers
     *
     * @param Request $request
     *
     * @return string
     */
    public function searchAndRender(Request $request)
    {
        return $this->render($this->search($request))->render();
    }
}
