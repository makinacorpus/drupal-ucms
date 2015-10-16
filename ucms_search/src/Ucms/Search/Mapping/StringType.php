<?php

namespace Ucms\Search\Mapping;

class StringType implements TypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function convert($value)
    {
        return (string)$value;
    }
}
