<?php

namespace MakinaCorpus\Ucms\Dashboard\Page;

use Symfony\Component\HttpFoundation\Request;

class PageBuilder
{
    private $twig;
    private $debug = false;
    private $defaultTemplate = 'table';
    private $templates = [];

    /**
     * Default constructor
     *
     * @param \Twig_Environment $twig
     * @param string[] $templates
     * @param string $defaultTemplate
     *   Default template
     */
    public function __construct(\Twig_Environment $twig, array $templates = [], $defaultTemplate = 'table')
    {
        $this->twig = $twig;
        $this->debug = $twig->isDebug();
        $this->defaultTemplate = $defaultTemplate;
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

        if (isset($this->templates[$this->defaultTemplate])) {
            return $this->templates[$this->defaultTemplate];
        }

        if ($this->debug) {
            trigger_error("page builder has no explicit 'default' template set, using first in array", E_USER_NOTICE);
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
    private function getTemplateFor($displayName = null)
    {
        if (empty($displayName)) {
            return $this->getDefaultTemplate();
        }

        if (!isset($this->templates[$displayName])) {
            if ($this->debug) {
                trigger_error(sprintf("%s: display has no associated template, switching to default", $displayName), E_USER_NOTICE);
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
        $state->setCurrentDisplay($request->get('display', $this->defaultTemplate));

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
     */
    public function render(PageResult $result, array $arguments = [])
    {
        $state = $result->getState();

        // Build display links
        // @todo Do it better...
        $displayLinks = [];
        foreach (array_keys($this->templates) as $display) {
            switch ($display) {
                case 'grid':
                    $displayIcon = 'th';
                    break;
                default:
                case 'table':
                    $displayIcon = 'th-list';
                    break;
            }
            $displayLinks[] = new Link($display, $result->getRoute(), ['display' => $display] + $result->getQuery(), $state->getCurrentDisplay() === $display, $displayIcon);
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
            ->load($this->getTemplateFor($state->getCurrentDisplay()))
            ->renderBlock('page', $arguments)
        ;
    }
}
