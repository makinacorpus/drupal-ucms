<?php

namespace MakinaCorpus\Ucms\Dashboard\Context;

use MakinaCorpus\Ucms\Dashboard\EventDispatcher\ContextPaneEvent;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class ContextPane
 * @package MakinaCorpus\Ucms\Dashboard\Context
 */
class ContextPane
{
    /**
     * @var mixed[]
     */
    private $items = [];

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    private $tabs = [];

    private $actions = [];

    /**
     * @var string
     */
    private $defaultTab = null;

    /**
     * ContextPane constructor.
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Lazy initialise the object
     */
    public function init()
    {
        $event = new ContextPaneEvent($this);
        $this->dispatcher->dispatch('ucms_dashboard.context_init', $event);
    }

    /**
     * Add an tab to the contextual pane
     *
     * @param mixed $key
     *   Tab identifier
     * @param string $label
     *   Human-readable lavbel
     * @param $icon
     *   Icon name for this tab
     * @param int $priority
     *   Will determine order
     * @return ContextPane
     */
    public function addTab($key, $label, $icon, $priority = 0)
    {
        $this->tabs[$key] = [
            'priority' => $priority,
            'key' => $key,
            'label' => $label,
            'icon' => $icon,
        ];

        return $this;
    }

    /**
     * Add an item to the contextual pane
     *
     * @param mixed $value
     *   Anything that can be rendered
     * @param string $tab
     *   Tab identifier
     * @param int $priority
     *   Will determine order
     *
     * @return ContextPane
     */
    public function add($value, $tab, $priority = 0)
    {
        if (!empty($value)) {
            $this->items[$tab][$priority][] = $value;
        }

        return $this;
    }

    /**
     * Is the context empty
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->items);
    }

    /**
     * Get all ordered pane items, indexed by tab key.
     *
     * @return array
     */
    public function getAll()
    {

        foreach ($this->getTabs() as $key => $label) {
            ksort($this->items[$key]);
        }

        return $this->items;
    }

    /**
     * Get all ordered tabs, indexed by tab key.
     *
     * @return array
     */
    public function getTabs()
    {
        uasort($this->tabs, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        return $this->tabs;
    }

    /**
     * Get the default tab key, or first tab key if none set.
     *
     * @return null
     */
    public function getDefaultTab()
    {
        return $this->defaultTab ? $this->defaultTab : reset($this->tabs)['key'];
    }

    /**
     * Set the default tab key.
     *
     * @param null $defaultTab
     * @return $this
     */
    public function setDefaultTab($defaultTab)
    {
        $this->defaultTab = $defaultTab;

        return $this;
    }

    /**
     * Add a group of actions for this context.
     *
     * @param $actions
     * @return $this
     */
    public function addActions($actions) {
        $this->actions[] = $actions;
        return $this;
    }

    /**
     * Get all actions for this context
     *
     * @return array
     */
    public function getActions() {
        return $this->actions;
    }
}