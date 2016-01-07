<?php

namespace Ucms\Search\Mapping;

/**
 * Field mapping type converter
 */
interface TypeInterface
{
    /**
     * Convert given value to anything ElasticSearch may understand
     *
     * @param mixed $value
     *
     * @return mixed
     *   Anything that fits as a field value in the ElasticSearch client
     *   param array
     */
    public function convert($value);
}
