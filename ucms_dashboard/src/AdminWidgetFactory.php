<?php

namespace MakinaCorpus\Ucms\Dashboard;

use Drupal\Core\Form\FormBuilderInterface;

use MakinaCorpus\Ucms\Dashboard\Action\ActionRegistry;
use MakinaCorpus\Ucms\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Ucms\Dashboard\Page\DisplayInterface;
use MakinaCorpus\Ucms\Dashboard\Page\Page;
use MakinaCorpus\Ucms\Dashboard\Page\PageBuilder;
use MakinaCorpus\Ucms\Dashboard\Page\TemplateDisplay;
use MakinaCorpus\Ucms\Dashboard\Table\AdminTable;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * God I hate to register more factories to the DIC, but we have some
 * dependencies that we should inject into pages, and only this allows
 * us to do ti properly
 */
final class AdminWidgetFactory
{
    private $formBuilder;
    private $defaultPageBuilder;
    private $pageBuilders = [];
    private $actionRegistry;
    private $eventDispatcher;
    private $debug;
    private $twig;

    /**
     * Default constructor
     *
     * @param FormBuilderInterface $formBuilder
     * @param PageBuilder $defaultPageBuilder,
     * @param ActionRegistry $actionRegistry
     * @param \Twig_Environment $twig
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        FormBuilderInterface $formBuilder,
        PageBuilder $defaultPageBuilder,
        ActionRegistry $actionRegistry,
        \Twig_Environment $twig,
        EventDispatcherInterface $eventDispatcher = null
    ) {
        $this->formBuilder = $formBuilder;
        $this->defaultPageBuilder = $defaultPageBuilder;
        $this->actionRegistry = $actionRegistry;
        $this->eventDispatcher = $eventDispatcher;
        $this->debug = $twig->isDebug();
        $this->twig = $twig;
    }

    /**
     * Register a page builder instance
     *
     * @param string $name
     * @param PageBuilder $pageBuilder
     */
    public function registerPageBuilder($name, PageBuilder $pageBuilder)
    {
        $this->pageBuilders[$name] = $pageBuilder;
    }

    /**
     * Get the page builder
     *
     * @param string $name
     *
     * @return PageBuilder
     */
    public function getPageBuilder($name = null)
    {
        if (null === $name) {
            return $this->defaultPageBuilder;
        }

        if (!isset($this->pageBuilders[$name])) {
            if ($this->debug) {
                trigger_error(sprintf("%s: page builder is not set, reverting to default", $name), E_USER_WARNING);
            }

            return $this->defaultPageBuilder;
        }

        return $this->pageBuilders[$name];
    }

    /**
     * Get page
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
    public function getPage(DatasourceInterface $datasource, DisplayInterface $display = null, $suggestions = null)
    {
        trigger_error("Please use the PageBuilder instead.", E_USER_DEPRECATED);

        return new Page($this->formBuilder, $this->actionRegistry, $datasource, $display, $suggestions);
    }

    /**
     * Get page using a template
     *
     * @param DatasourceInterface $datasource
     *
     * @deprecated
     *   Please use the PageBuilder object and service instead
     */
    public function getPageWithTemplate(DatasourceInterface $datasource, $templateName)
    {
        trigger_error("Please use the PageBuilder instead.", E_USER_DEPRECATED);

        return new Page($this->formBuilder, $this->actionRegistry, $datasource, new TemplateDisplay($this->twig, $templateName));
    }

    /**
     * Get a new admin table
     *
     * @param string $name
     * @param mixed $attributes
     */
    public function getTable($name, $attributes = [])
    {
        return new AdminTable($name, $attributes, $this->eventDispatcher);
    }
}
