<?php

namespace MakinaCorpus\Ucms\Search\Aggs;

use MakinaCorpus\Ucms\Search\Response;
use MakinaCorpus\Ucms\Search\Search;

/**
 * Represent an Elastic Search value count aggregation
 *
 * https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-valuecount-aggregation.html
 */
class ValueCount
{
    /**
     * @var string
     */
    private $field;

    /**
     * @var int
     */
    private $result = 0;

    /**
     * Default constructor
     *
     * @param string $field
     *   Field name
     */
    public function __construct($field)
    {
        $this->field = $field;
    }

    /**
     * {@inheritDoc}
     */
    public function prepareQuery(Search $search, $query)
    {
    }

    /**
     * Get aggregation name
     *
     * @return string
     */
    public function getAggregationName()
    {
        return "value_count_" . $this->field;
    }

    /**
     * Get counted value
     *
     * @param int
     */
    public function getCount()
    {
        return $this->result;
    }

    /**
     * {@inheritDoc}
     */
    public function buildQueryData(Search $search, $query)
    {
        return [
            $this->getAggregationName() => [
                "value_count" => [
                    "field" => $this->field,
                ],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function parseResponse(Search $search, Response $response, $raw)
    {
        $name = $this->getAggregationName();

        if (!isset($raw['aggregations'][$name])) {
            throw new \RuntimeException(sprintf("Aggregation '%s' is missing from response", $name));
        }

        $this->result = (int)$raw['aggregations'][$name]['value'];
    }
}
