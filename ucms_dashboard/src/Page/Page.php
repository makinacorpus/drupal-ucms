<?php

namespace MakinaCorpus\Ucms\Dashboard\Page;

use Drupal\Core\Form\FormBuilderInterface;

use MakinaCorpus\Ucms\Dashboard\Action\ActionRegistry;

/**
 * @deprecated
 *   Please use the PageBuilder object and service instead
 */
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

    /**
     * Backward compatiblity, render page template block
     *
     * @param string $name
     * @param mixed[] $context
     *
     * @return string
     */
    private function renderBlock($name, $context)
    {
        return \Drupal::service('twig')->loadTemplate('module:ucms_dashboard:views/Page/page.html.twig')->renderBlock($name, $context);
    }

    public function render($query = [], $route = '/')
    {
        trigger_error("Please use the PageBuilder instead.", E_USER_DEPRECATED);

        $query = $this->buildQuery($query);
        $state = new PageState();
        $fixedQuery = Filter::fixQuery($query); // @todo this is ugly

        $this->datasource->init($fixedQuery, $this->baseQuery);

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

        // Build the page state gracefully, this uglyfies the code but it does
        // help to reduce code within the datasources
        $state->setSortField($sortManager->getCurrentField($fixedQuery));
        $state->setSortOrder($sortManager->getCurrentOrder($fixedQuery));
        if (empty($query[$state->getPageParameter()])) {
            $state->setRange(24);
        } else {
            $state->setRange(24, $query[$state->getPageParameter()]);
        }

        $items = $this->datasource->getItems($fixedQuery, $state);

        // Initialize pager only after the query has been run, datasource is
        // responsible for setting the total count
        if ($state->hasTotalItemCount()) {
            pager_default_initialize($state->getTotalItemCount(), $state->getLimit());
        }

        $build = [
            '#theme'      => $this->getThemeFunctionName('ucms_dashboard_page_list'),
            '#filters'    => [],
            '#display'    => $this->display,
            '#items'      => $items,
            '#pager'      => ['#theme' => $this->getThemeFunctionName('pager')],
        ];

        if ($sortIsEnabled) {
            $sortManager->prepare($route, $query);
            $build += [
                '#sort_field' => '<span style="position:relative;">' . $this->renderBlock('sort_links_field', ['sort' => $sortManager, 'query' => $query]) . '</span>',
                '#sort_order' => '<span style="position:relative;">' . $this->renderBlock('sort_links_order', ['sort' => $sortManager, 'query' => $query]) . '</span>',
            ];
        }

        $filters = $this->datasource->getFilters($query);
        if ($filters) {
            foreach ($filters as $filter) {
                if (isset($this->baseQuery[$filter->getField()])) {
                    continue; // Drop forced filters
                }
                $filter->prepare($route, $query);
                $build['#filters'][$filter->getField()] = $this->renderBlock('filter', ['filter' => $filter]);
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
