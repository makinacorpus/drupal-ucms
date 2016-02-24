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
        $pageState = new PageState();
        $query = [];

        $pageState->setRange(6);
        $pageState->setSortOrder(PageState::SORT_DESC);

        $this->datasource->init($query);

        $display = $this->getDisplay($query, $pageState);
        $display->prepareFromQuery($query);

        return $display->render($this->datasource->getItems($query, $pageState));
    }
}
