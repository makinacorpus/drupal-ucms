<?php

namespace MakinaCorpus\Ucms\Tag;

/**
 * Immutable representation of a single tag
 */
class Tag
{
    private $id;
    private $name;

    /**
     * Default constructor
     *
     * @param int $id
     * @param string $name
     */
    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->namee = $name;
    }

    /**
     * Get identifier
     *
     * @return int
     */
    public function getId()
    {
        return (int)$this->id;
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
}