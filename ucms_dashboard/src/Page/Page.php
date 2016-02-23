<?php

namespace MakinaCorpus\Ucms\Dashboard\Page;

use Drupal\Core\Form\FormBuilderInterface;

use MakinaCorpus\Ucms\Dashboard\Action\ActionRegistry;

class Page
{
    /**
     * @var DatasourceInterface
     */
    private $datasource;

    /**
     * @var DisplayInterface
     */
    private $display;

    /**
     * Template suggestions
     *
     * @var string[]
     */
    private $suggestions = [];

    /**
     * @var FormBuilderInterface
     */
    private $formBuilder;

    /**
     * @var ActionRegistry
     */
    private $actionRegistry;

    /**
     * @var string[]
     */
    private $baseQuery = [];

    /**
     * Default constructor
     *
     * @param FormBuilderInterface $formBuilder
     * @param ActionRegistry $actionRegistry
     * @param DatasourceInterface $datasource
     * @param DisplayInterface $display
     * @param string[] $suggestions
     */
    public function __construct(
        FormBuilderInterface $formBuilder,
        ActionRegistry $actionRegistry,
        DatasourceInterface $datasource,
        DisplayInterface $display,
        $suggestions = null
    ) {
        $this->datasource = $datasource;
        $this->display = $display;
        $this->suggestions = $suggestions ? $suggestions : [];
        $this->formBuilder = $formBuilder;
        $this->actionRegistry = $actionRegistry;
    }

    /**
     * Get theme function name using current page suggestion
     *
     * @param string $hook
     *
     * @return string
     */
    private function getThemeFunctionName($hook)
    {
        return implode('__', [$hook] + $this->suggestions);
    }

    /**
     * Set base query
     *
     * Base query will remove anything from it that is not a defined filter,
     * but will apply per default the others, removing them from the filter
     * list
     *
     * @param string[]
     *
     * @return Page
     */
    public function setBaseQuery($baseQuery)
    {
        $this->baseQuery = $baseQuery;

        return $this;
    }

    /**
     * Build and return current usable query from both the environment and the
     * set base query, if any
     *
     * @return string[]
     */
    private function buildQuery($query = null)
    {
        if (null === $query) {
            $query = drupal_get_query_parameters();
        }
        // @todo This should be injected
        return $this->baseQuery + $query;
    }

    public function render($query = [], $route = '/')
    {
        $query = $this->buildQuery($query);
        //$state = new PageState();
        $fixedQuery = LinksFilterDisplay::fixQuery($query); // @todo this is ugly

        $this->datasource->init($fixedQuery);

        $sortManager = new SortManager();
        $sortIsEnabled = false;

        if ($sortFields = $this->datasource->getSortFields($query)) {
            $sortManager->setFields($sortFields);
            // Do not set the sort order links if there is no field to sort on
            if ($sortDefault = $this->datasource->getDefaultSort()) {
                $sortManager->setDefault(...$sortDefault);
            }
            $sortIsEnabled = true;
        }

        $this->display->prepareFromQuery($query);
        if ($this->display instanceof AbstractDisplay) {
            $this->display->setActionRegistry($this->actionRegistry);
        }

        $items = $this
            ->datasource
            ->getItems(
                $fixedQuery,
                $sortManager->getCurrentField($fixedQuery),
                $sortManager->getCurrentOrder($fixedQuery)
            )
        ;

        $build = [
            '#theme'      => $this->getThemeFunctionName('ucms_dashboard_page_list'),
            '#filters'    => [],
            '#display'    => $this->display,
            '#items'      => $items,
            '#pager'      => ['#theme' => $this->getThemeFunctionName('pager')],
        ];

        if ($sortIsEnabled) {
            $build += [
                '#sort_field' => $sortManager->buildFieldLinks($fixedQuery, $route),
                '#sort_order' => $sortManager->builOrderLinks($fixedQuery, $route),
            ];
        }

        $filters = $this->datasource->getFilters($query);
        if ($filters) {
            foreach ($filters as $filter) {
                if (isset($this->baseQuery[$filter->getField()])) {
                    continue; // Drop forced filters
                }
                $build['#filters'][$filter->getField()] = $filter->build($query, $route);
            }
        }

        if ($this->datasource->hasSearchForm()) {
            $build['#search'] = $this
                ->formBuilder
                ->getForm(
                    '\MakinaCorpus\Ucms\Dashboard\Page\SearchForm',
                    $fixedQuery,
                    $this->datasource->getSearchFormParamName()
                )
            ;
        }

        return $build;
    }
}
