<?php

namespace MakinaCorpus\Ucms\Drop;

class DragItem
{
    private $type;
    private $id;

    /**
     * Default constructor.
     *
     * @param string $type
     * @param scalar $id
     */
    public function __construct($type, $id)
    {
        $this->type = $type;
        $this->id = $id;
    }

    /**
     * Get object type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get object identifier
     *
     * @return scalar
     */
    public function getId()
    {
        return $this->id;
    }
}

