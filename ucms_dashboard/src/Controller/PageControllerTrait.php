<?php

namespace MakinaCorpus\Ucms\Dashboard\Controller;

use MakinaCorpus\Ucms\Dashboard\AdminWidgetFactory;
use MakinaCorpus\Ucms\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Ucms\Dashboard\Page\DisplayInterface;
use MakinaCorpus\Ucms\Dashboard\Page\Page;
use MakinaCorpus\Ucms\Dashboard\Table\AdminTable;
use MakinaCorpus\Ucms\Dashboard\Page\PageBuilder;

use Symfony\Component\HttpFoundation\Request;

trait PageControllerTrait
{
    /**
     * Get page factory
     *
     * @return AdminWidgetFactory
     */
    protected function getWidgetFactory()
    {
        return $this->get('ucms_dashboard.admin_widget_factory');
    }

    /**
     * Create page
     *
     * @param DatasourceInterface $datasource
     * @param DisplayInterface $display
     * @param string[] $suggestions
     *
     * @return Page
     *
     * @deprecated
     *   Please use the PageBuilder object and service instead
     */
    protected function createPage(DatasourceInterface $datasource, DisplayInterface $display = null, $suggestions = null)
    {
        trigger_error("Please use the PageBuilder instead.", E_USER_DEPRECATED);

        return $this->getWidgetFactory()->getPage($datasource, $display, $suggestions);
    }

    /**
     * Create page from a template
     *
     * @param DatasourceInterface $datasource
     * @param string $templateName
     *
     * @return Page
     *
     * @deprecated
     *   Please use the PageBuilder object and service instead
     */
    protected function createTemplatePage(DatasourceInterface $datasource, $templateName)
    {
        trigger_error("Please use the PageBuilder instead.", E_USER_DEPRECATED);

        return $this->getWidgetFactory()->getPageWithTemplate($datasource, $templateName);
    }

    /**
     * Get the page builder
     *
     * @param string $name
     *
     * @return PageBuilder
     */
    protected function getPageBuilder($name = null, Request $request)
    {
        return $this->getWidgetFactory()->getPageBuilder($name, $request);
    }

    /**
     * Render page
     */
    protected function renderPage(Request $request, DatasourceInterface $datasource, $templateName = null, array $arguments = [])
    {
        $builder = $this->getPageBuilder();
        $result = $builder->search($datasource, $request);

        return $builder->render($result, $arguments, $templateName);
    }

    /**
     * Create an admin table
     *
     * @param string $name
     *   Name will be the template suggestion, and the event name, where the
     *   event name will be admin:table:NAME
     * @param mixed $attributes
     *
     * @return AdminTable
     *
     */
    protected function createAdminTable($name, array $attributes = [])
    {
        return $this->getWidgetFactory()->getTable($name, $attributes);
    }

    /**
     * Given some admin table, abitrary add a new section with attributes within
     *
     * @param AdminTable $table
     * @param mixed[] $attributes
     */
    protected function addArbitraryAttributesToTable(AdminTable $table, array $attributes = [], $title = null)
    {
        if (!$attributes) {
            return;
        }

        if (!$title) {
            $title = "Attributes";
        }

        $table->addHeader($title, 'attributes');

        foreach ($attributes as $key => $value) {

            if (is_scalar($value)) {
                $value = check_plain($value);
            } else {
                $value = '<pre>' . json_encode($value, JSON_PRETTY_PRINT) . '</pre>';
            }

            $table->addRow(check_plain($key), $value, $key);
        }
    }
}
