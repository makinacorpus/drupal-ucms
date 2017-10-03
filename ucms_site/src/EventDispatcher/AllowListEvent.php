<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use Symfony\Component\EventDispatcher\Event;

/**
 * Very generic event that will be used in various admin sections
 */
class AllowListEvent extends Event
{
    const EVENT_THEMES = 'ucms_site:allowed_list_themes';
    const THEMES = "themes";

    private $currentList = [];
    private $originalList = [];
    private $target;

    /**
     * Default constructor
     *
     * @param string $target
     * @param string[] $originalList
     */
    public function __construct($target, $originalList = [])
    {
        $this->currentList = $this->originalList = $originalList;
        $this->target = $target;
    }

    /**
     * Get which list is being restricted
     *
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Get currently allowed items carried by this event
     *
     * @return string[]
     */
    public function getAllowedItems()
    {
        return $this->currentList;
    }

    /**
     * Remove item from list
     *
     * @param string $item
     */
    public function remove($item)
    {
        foreach ($this->currentList as $index => $candidate) {
            if ($candidate === $item) {
                unset($this->currentList[$index]);
            }
        }
    }

    /**
     * Add item into list
     *
     * @param string $item
     */
    public function add($item)
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
     * Remove items not in
     *
     * @param array $items
     */
    public function removeNotIn(array $items)
    {
        $this->currentList = array_intersect($items, $this->currentList);
    }
}
