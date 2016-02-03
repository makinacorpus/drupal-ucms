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
     */
    public function __construct(FormBuilderInterface $formBuilder, ActionRegistry $actionRegistry)
    {
        $this->formBuilder = $formBuilder;
        $this->actionRegistry = $actionRegistry;
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
}
