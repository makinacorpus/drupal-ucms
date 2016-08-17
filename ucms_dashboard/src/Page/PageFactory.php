<?php

namespace MakinaCorpus\Ucms\Dashboard\Page;

use Drupal\Core\Form\FormBuilderInterface;

use MakinaCorpus\Ucms\Dashboard\Action\ActionRegistry;

/**
 * God I hate to register more factories to the DIC, but we have some
 * dependencies that we should inject into pages, and only this allows
 * us to do ti properly
 */
class PageFactory
{
    private $formBuilder;
    private $actionRegistry;
    private $twig;

    /**
     * Default constructor
     *
     * @param FormBuilderInterface $formBuilder
     * @param ActionRegistry $actionRegistry
     */
    public function __construct(FormBuilderInterface $formBuilder, ActionRegistry $actionRegistry, \Twig_Environment $twig)
    {
        $this->formBuilder = $formBuilder;
        $this->actionRegistry = $actionRegistry;
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
    public function get(DatasourceInterface $datasource, DisplayInterface $display, $suggestions = null)
    {
        return new Page($this->formBuilder, $this->actionRegistry, $datasource, $display, $suggestions);
    }

    /**
     * Get page using a template
     *
     * @param DatasourceInterface $datasource
     */
    public function getTemplate(DatasourceInterface $datasource, $templateName)
    {
        return new Page($this->formBuilder, $this->actionRegistry, $datasource, new TemplateDisplay($this->twig, $templateName));
    }
}
