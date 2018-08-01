<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use Symfony\Component\EventDispatcher\Event;

class AllowListEvent extends Event
{
    const EVENT_THEMES = 'ucms_site:allowed_list_themes';
    const THEMES = "themes";

    private $currentList = [];
    private $originalList = [];
    private $target;

    /**
     * Default constructor
     */
    public function __construct(string $target, array $originalList = [])
    {
        $this->currentList = $this->originalList = $originalList;
        $this->target = $target;
    }

    /**
     * Get which list is being restricted
     */
    public function getTarget(): string
    {
        return $this->target ?? '';
    }

    /**
     * Get currently allowed items carried by this event
     */
    public function getAllowedItems(): array
    {
        return $this->currentList;
    }

    /**
     * Remove item from list
     */
    public function remove(string $item)
    {
        foreach ($this->currentList as $index => $candidate) {
            if ($candidate === $item) {
                unset($this->currentList[$index]);
            }
        }
    }

    /**
     * Add item into list
     */
    public function add(string $item)
    {
        // Disallow items outside of original boundaries
        if (!in_array($item, $this->originalList)) {
            return;
        }
        if (!in_array($item, $this->currentList)) {
            $this->currentList[] = $item;
        }
    }

    /**
     * Remove items not in the given array
     */
    public function removeNotIn(array $items)
    {
        $this->currentList = array_intersect($items, $this->currentList);
    }
}
