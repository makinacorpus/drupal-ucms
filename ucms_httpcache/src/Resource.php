<?php

namespace MakinaCorpus\Ucms\HttpCache;

/**
 * Represents a resource being displayed on some URL
 */
class Resource
{
    private $type;
    private $id;

    public function __construct($type, $id)
    {
        $this->type = $type;
        $this->id = $id;
    }
}