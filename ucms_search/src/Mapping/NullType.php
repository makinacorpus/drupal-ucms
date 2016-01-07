<?php

namespace Ucms\Search\Mapping;

class NullType implements TypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function convert($value)
    {
        return (string)$value;
    }
}
