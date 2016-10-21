<?php

namespace MakinaCorpus\Ucms\Dashboard;

use Drupal\Core\Form\FormBuilderInterface;

use MakinaCorpus\Ucms\Dashboard\Action\ActionRegistry;
use MakinaCorpus\Ucms\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Ucms\Dashboard\Page\DisplayInterface;
use MakinaCorpus\Ucms\Dashboard\Page\Page;
use MakinaCorpus\Ucms\Dashboard\Page\TemplateDisplay;
use MakinaCorpus\Ucms\Dashboard\Table\AdminTable;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * God I hate to register more factories to the DIC, but we have some
 * dependencies that we should inject into pages, and only this allows
 * us to do ti properly
 */
class AdminWidgetFactory
{
    private $formBuilder;
    private $actionRegistry;
    private $eventDispatcher;
    private $twig;

    /**
     * Default constructor
     *
     * @param FormBuilderInterface $formBuilder
     * @param ActionRegistry $actionRegistry
     * @param \Twig_Environment $twig
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        FormBuilderInterface $formBuilder,
        ActionRegistry $actionRegistry,
        \Twig_Environment $twig,
        EventDispatcherInterface $eventDispatcher = null
    ) {
        $this->formBuilder = $formBuilder;
        $this->actionRegistry = $actionRegistry;
        $this->eventDispatcher = $eventDispatcher;
        $this->twig = $twig;
    }

    /**
     * Get page
     *
     * @param DatasourceInterface $datasource
     * @param DisplayInterface $display
     * @param string[] $suggestions
     *
     * @return Page
     */
    public function getPage(DatasourceInterface $datasource, DisplayInterface $display = null, $suggestions = null)
    {
        return new Page($this->formBuilder, $this->actionRegistry, $datasource, $display, $suggestions);
    }

    /**
     * Get page using a template
     *
     * @param DatasourceInterface $datasource
     */
    public function getPageWithTemplate(DatasourceInterface $datasource, $templateName)
    {
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
