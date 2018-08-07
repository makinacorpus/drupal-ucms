<?php

namespace MakinaCorpus\Ucms\Layout;

class Region implements \IteratorAggregate, \Countable
{
    /**
     * @var int
     */
    private $name;

    /**
     * @var Item[]
     */
    private $items = [];

    /**
     * @var boolean
     */
    private $isUpdated = false;

    /**
     * Default constructor
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Is this instance updated
     *
     * @return boolean
     */
    public function isUpdated()
    {
        return $this->isUpdated;
    }

    /**
     * Set the updated status of this instance
     *
     * @param boolean $toggle
     *
     * @return Region
     */
    public function toggleUpdateStatus($toggle)
    {
        $this->isUpdated = (bool)$toggle;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add item at the specified position
     *
     * @param Item $item
     * @param int $position
     *   If no position specified or int is higher to the higher bound,
     *   append the item, for prepending set 0
     */
    public function addAt(Item $item, $position = null)
    {
        if (0 === $position) {
            array_unshift($this->items, $item);
        } else if (null === $position) {
            $this->items[] = $item;
        } else if (count($this->items) <= $position) {
            $this->items[] = $item;
        } else {
            array_splice($this->items, $position, 0, [$item]);
        }

        $this->isUpdated = true;

        return $this;
    }

    /**
     * Remove item at specified position
     *
     * @param int $position
     *
     * @return Region
     */
    public function removeAt($position)
    {
        if (!is_numeric($position) || $position < 0) {
            throw new \InvalidArgumentException();
        }

        if ($position < count($this->items)) {
            array_splice($this->items, $position, 1);
        }

        $this->isUpdated = true;

        return $this;
    }

    /**
     * Prepend item
     *
     * @param Item $item
     *
     * @return Region
     */
    public function prepend(Item $item)
    {
        $this->addAt($item, 0);

        return $this;
    }

    /**
     * Append item
     *
     * @param Item $item
     *
     * @return Region
     */
    public function append(Item $item)
    {
        $this->addAt($item, null);

        return $this;
    }

    /**
     * Get all node identifiers
     *
     * @return int[]
     */
    public function getAllNodeIds()
    {
        $ret = [];
        foreach ($this->items as $item) {
          $ret[] = $item->getNodeId();
        }
        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        foreach ($this->items as $value) {
            yield $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->items);
    }
}
