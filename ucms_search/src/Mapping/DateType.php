<?php

namespace MakinaCorpus\Ucms\Search\Mapping;

class DateType implements TypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function convert($value)
    {
        if ($value instanceof \DateTime) {
            return $value->format(\DateTime::ISO8601);
        }

        if (is_string($value)) {
            if ($date = new \DateTime($value)) {
                return $date->format(\DateTime::ISO8601);
            }
        }

        if (is_int($value)) {
            if ($date = new \DateTime('@' . $value)) {
                return $date->format(\DateTime::ISO8601);
            }
        }

        return (string)$value;
    }
}
