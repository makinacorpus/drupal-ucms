<?php

namespace MakinaCorpus\Ucms\Dashboard\Controller;

use MakinaCorpus\Ucms\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Ucms\Dashboard\Page\DisplayInterface;
use MakinaCorpus\Ucms\Dashboard\Page\Page;
use MakinaCorpus\Ucms\Dashboard\Page\PageFactory;

trait PageControllerTrait
{
    /**
     * Get page factory
     *
     * @return PageFactory
     */
    protected function getPageFactory()
    {
        return $this->get('ucms_dashboard.page_factory');
    }

    /**
     * Create page
     *
     * @param DatasourceInterface $datasource
     * @param DisplayInterface $display
     * @param string[] $suggestions
     *
     * @return Page
     */
    protected function createPage(DatasourceInterface $datasource, DisplayInterface $display, $suggestions = null)
    {
        return $this->getPageFactory()->get($datasource, $display, $suggestions);
    }
}
