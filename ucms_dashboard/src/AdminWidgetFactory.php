<?php

namespace MakinaCorpus\Ucms\Dashboard;

use Drupal\Core\Form\FormBuilderInterface;

use MakinaCorpus\Ucms\Dashboard\Action\ActionRegistry;
use MakinaCorpus\Ucms\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Ucms\Dashboard\Page\DisplayInterface;
use MakinaCorpus\Ucms\Dashboard\Page\Page;
use MakinaCorpus\Ucms\Dashboard\Page\PageBuilder;
use MakinaCorpus\Ucms\Dashboard\Page\PageTypeInterface;
use MakinaCorpus\Ucms\Dashboard\Page\TemplateDisplay;
use MakinaCorpus\Ucms\Dashboard\Table\AdminTable;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * God I hate to register more factories to the DIC, but we have some
 * dependencies that we should inject into pages, and only this allows
 * us to do it properly
 */
final class AdminWidgetFactory
{
    private $container;
    private $formBuilder;
    private $pageTypes = [];
    private $actionRegistry;
    private $eventDispatcher;
    private $debug;
    private $twig;

    /**
     * Default constructor
     *
     * @param ContainerInterface $container
     * @param FormBuilderInterface $formBuilder
     * @param PageBuilder $defaultPageBuilder,
     * @param ActionRegistry $actionRegistry
     * @param \Twig_Environment $twig
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ContainerInterface $container,
        FormBuilderInterface $formBuilder,
        ActionRegistry $actionRegistry,
        \Twig_Environment $twig,
        EventDispatcherInterface $eventDispatcher = null
    ) {
        $this->container = $container;
        $this->formBuilder = $formBuilder;
        $this->actionRegistry = $actionRegistry;
        $this->eventDispatcher = $eventDispatcher;
        $this->debug = $twig->isDebug();
        $this->twig = $twig;
    }

    /**
     * Register page types
     *
     * @param string[] $types
     *   Keys are names, values are service identifiers
     */
    public function registerPageTypes($types)
    {
        $this->pageTypes = $types;
    }

    /**
     * Get page type
     *
     * @param string $name
     *
     * @return PageTypeInterface
     */
    public function getPageType($name)
    {
        // @todo rewrite this better
        if (!isset($this->pageTypes[$name])) {
            try {
                $instance = $this->container->get($name);
                if (!$instance instanceof PageTypeInterface) {
                    throw new \InvalidArgumentException(sprintf("page builder with service id '%s' does not implement %s", $name, PageTypeInterface::class));
                }
                $this->pageTypes[$name] = $instance;

                return $instance;

            } catch (ServiceNotFoundException $e) {
                throw new \InvalidArgumentException(sprintf("page builder with service id '%s' does not exist in container", $name));
            }
        }

        $instance = $this->pageTypes[$name];

        if (is_string($instance)) {
            $instance = $this->container->get($instance);
            if (!$instance instanceof PageTypeInterface) {
                throw new \InvalidArgumentException(sprintf("page builder with service id '%s' does not implement %s", $name, PageTypeInterface::class));
            }
            $this->pageTypes[$name] = $instance;
        }

        return $instance;
    }

    /**
     * Get the page builder
     *
     * @param string $name
     *
     * @return PageBuilder
     */
    public function getPageBuilder($name, Request $request)
    {
        $type = $this->getPageType($name);
        $builder = new PageBuilder($this->twig);

        $type->build($builder, $request);

        return $builder;
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
