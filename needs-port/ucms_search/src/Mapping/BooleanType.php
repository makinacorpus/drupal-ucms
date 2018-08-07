<?php

namespace MakinaCorpus\Ucms\Search\Mapping;

class BooleanType implements TypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function convert($value)
    {
        return (bool)$value;
    }
}
