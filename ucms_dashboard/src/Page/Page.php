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
        $this->suggestions = $suggestions;
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

    public function render()
    {
        $query = drupal_get_query_parameters();
        // @todo
        //   ugly
        $fixedQuery = LinksFilterDisplay::fixQuery($query);

        $this->datasource->init($fixedQuery);

        $sortManager = new SortManager();
        if ($sortFields = $this->datasource->getSortFields($query)) {
            $sortManager->setFields($sortFields);
        }
        if ($sortDefault = $this->datasource->getDefaultSort()) {
            $sortManager->setDefault(...$sortDefault);
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
            '#sort_field' => $sortManager->buildFieldLinks($fixedQuery),
            '#sort_order' => $sortManager->builOrderLinks($fixedQuery),
        ];

        foreach ($this->datasource->getFilters($query) as $index => $filter) {
            $build['#filters'][$index] = $filter->build($query);
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
