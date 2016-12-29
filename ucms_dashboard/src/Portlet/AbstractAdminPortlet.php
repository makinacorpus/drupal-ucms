<?php

namespace MakinaCorpus\Ucms\Dashboard\Portlet;

use MakinaCorpus\Ucms\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Ucms\Dashboard\Page\DisplayInterface;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;

abstract class AbstractAdminPortlet extends AbstractPortlet
{
    /**
     * @var DatasourceInterface
     */
    private $datasource;

    /**
     * Default constructor
     *
     * @param DatasourceInterface $datasource
     */
    public function __construct(DatasourceInterface $datasource)
    {
        $this->datasource = $datasource;
    }

    /**
     * Get display and prepare query
     *
     * @param string[] $query
     * @param PageState $pageState
     *
     * @return DisplayInterface
     */
    abstract protected function getDisplay(&$query, PageState $pageState);

    /**
     * {@inheritDoc}
     */
    public function getContent()
    {
        try {
            $pageState = new PageState();
            $query = [];

            $pageState->setRange(6);

            if ($defaultSort = $this->datasource->getDefaultSort()) {
                if (isset($defaultSort[0])) {
                    $pageState->setSortField($defaultSort[0]);
                }
                if (isset($defaultSort[1])) {
                    $pageState->setSortOrder($defaultSort[1]);
                }
            }

            $this->datasource->init($query, []);

            $display = $this->getDisplay($query, $pageState);
            $display->prepareFromQuery($query);

            return $display->render($this->datasource->getItems($query, $pageState));

        } catch (\Exception $e) {

            // @todo log me !!!!

            // Régis doesn't like when Elastic is down. Elastic does business
            // stuff therefore any business-critical component failing should
            // never be caught, but without this main dashboard page might
            // not be reachable at all
            return $e->getMessage() . '<br/><pre>' . $e->getTraceAsString() . '</pre>';
        }
    }
}
