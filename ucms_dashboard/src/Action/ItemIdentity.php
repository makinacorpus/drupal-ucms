<?php

namespace MakinaCorpus\Ucms\Dashboard\Action;

final class ItemIdentity
{
    public $id;
    public $type;

    public function __construct(string $type, string $id)
    {
        $this->id = $id;
        $this->type = $type;
    }

    public function __set($name, $value)
    {
        throw new \BadMethodCallException("Object is immutable.");
    }
}
