<?php

namespace MakinaCorpus\Ucms\Dashboard\Context;

use MakinaCorpus\Ucms\Dashboard\EventDispatcher\ContextPaneEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ContextPane
{
    private $items = [];

    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher) {
        $this->dispatcher = $dispatcher;
    }

    public function init() {
        $event = new ContextPaneEvent($this);
        $this->dispatcher->dispatch('ucms_dashboard.context_init', $event);
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
    public function add($value, $tab = null, $priority = 0)
    {
        if (!empty($value)) {
            $this->items[$priority][] = $value;
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
     * Render the pane
     */
    public function getAll()
    {
        $ret = [];

        ksort($this->items);

        foreach ($this->items as $items) {
            foreach ($items as $item) {
                $ret[] = $item;
            }
        }

        return $ret;
    }
}
