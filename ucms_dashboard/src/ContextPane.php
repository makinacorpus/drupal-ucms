<?php

namespace MakinaCorpus\Ucms\Dashboard;

class ContextPane
{
    private $items = [];

    /**
     * Add an item to the contextual pane
     *
     * @param mixed $value
     *   Anything that can be rendered
     * @param int $priority
     *   Will determine order
     *
     * @return ContextPane
     */
    public function add($value, $priority = 0)
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
