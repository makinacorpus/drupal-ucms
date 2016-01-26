<?php

namespace MakinaCorpus\Ucms\Dashboard\Page;

use MakinaCorpus\Ucms\Dashboard\Action\Action;

/**
 * Use this class to interface with the main dashboard page layout
 *
 * You won't need to care about rendering or layout, just implement this in
 * order to expose your data.
 *
 * @see \MakinaCorpus\Ucms\Contrib\NodeDatasource
 *   For a complete working exemple (which was the original prototype)
 */
interface DatasourceInterface
{
    /**
     * Get ready to display filters
     *
     * @param string $query
     *   Incomming query parameters
     *
     * @return FilterDisplayInterface[]
     *   Keys does not matter, while values should be render arrays
     */
    public function getFilters($query);

    /**
     * Get display handler
     *
     * @return DisplayInterface
     */
    public function getDisplay();

    /**
     * This method is called before all others, if some operations such as the
     * filters building needing a request to the backend, then this is the place
     * where you should probably do it
     *
     * @param string $query
     *   Incomming query parameters
     */
    public function init($query);

    /**
     * Get items to display
     *
     * This should NOT return rendered items but loaded items or item
     * identifiers depending upon your implementation: only the Display
     * instance will really display items, since it may change the display
     * depending upon current context
     *
     * @param string $query
     *   Incomming query parameters
     *
     * @return mixed[]
     */
    public function getItems($query);

    /**
     * Get item actions
     *
     * @param mixed $item
     *   Anything you returned in getItems()
     *
     * @return Action[]
     */
    public function getItemActions($item);

    /**
     * Does this connector implements a full text search form
     *
     * @return boolean
     */
    public function hasSearchForm();

    /**
     * Return the GET parameter name for the search form
     *
     * If null is returned, SearchForm::DEFAULT_PARAM_NAME will be used
     *
     * @return string
     */
    public function getSearchFormParamName();
}
