<?php

namespace MakinaCorpus\Ucms\Dashboard\Page;

use Drupal\Core\Form\FormBuilderInterface;

class Page
{
    /**
     * @var DatasourceInterface
     */
    private $datasource;

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
     * Default constructor
     *
     * @param DatasourceInterface $datasource
     * @param string[] $suggestions
     */
    public function __construct(DatasourceInterface $datasource, $suggestions = null)
    {
        $this->datasource = $datasource;
        $this->suggestions = $suggestions;

        // @todo
        //  - this MUST be injected
        $this->formBuilder = \Drupal::formBuilder();
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
        $query = $_GET;

        $this->datasource->init($query);

        $display = $this->datasource->getDisplay();
        $display->prepareFromQuery($query);

        $build = [
            '#theme'    => $this->getThemeFunctionName('ucms_dashboard_page_list'),
            '#filters'  => $this->datasource->getFilters($query),
            '#display'  => $display,
            '#items'    => $this->datasource->getItems($query),
            '#pager'    => ['#theme' => $this->getThemeFunctionName('pager')],
        ];

        if ($this->datasource->hasSearchForm()) {
            $build['#search'] = $this
                ->formBuilder
                ->getForm(
                    '\MakinaCorpus\Ucms\Dashboard\Page\SearchForm',
                    $query,
                    $this->datasource->getSearchFormParamName()
                )
            ;
        }

        return $build;
    }
}
