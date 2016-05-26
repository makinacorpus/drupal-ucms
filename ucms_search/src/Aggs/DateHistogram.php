<?php

namespace MakinaCorpus\Ucms\Search\Aggs;

use MakinaCorpus\Ucms\Search\Response;
use MakinaCorpus\Ucms\Search\Search;

/**
 * Represent an Elastic Search date histogram aggregation
 *
 * https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-bucket-datehistogram-aggregation.html
 * https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-date-format.html
 */
class DateHistogram implements AggInterface
{
    const YEAR = "year";
    const QUARTER = "quarter";
    const MONTH = "month";
    const WEEK = "week";
    const DAY = "day";
    const HOUR = "hour";
    const MINUTE = "minute";
    const SECOND = "second";

    private $field;
    private $interval;
    private $dateFormat;
    private $results;

    /**
     * Default constructor
     *
     * @param string $field
     *   Field name
     * @param string $interval
     *   One of these object constants or a date format string
     * @param string $dateFormat
     *   See elastic search mapping date format
     */
    public function __construct($field, $interval = self::MONTH, $dateFormat = null)
    {
        $this->field = $field;
        $this->interval = $interval;
        $this->dateFormat = $dateFormat;
    }

    /**
     * {@inheritDoc}
     */
    public function prepareQuery(Search $search, $query)
    {
    }

    /**
     * Get the date range interval
     *
     * @return string
     *   One of this class constants
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * Get aggregation name
     *
     * @return string
     */
    public function getAggregationName()
    {
        return "date_histogram_" . $this->field;
    }

    /**
     * Get top hit results document identifiers
     *
     * @param int[][]
     *   First dimension keys are field values, second dimension values
     *   are ordered identifiers by query score desc
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * {@inheritDoc}
     */
    public function buildQueryData(Search $search, $query)
    {
        $data = [
            "date_histogram" => [
                'field' => $this->field,
                'interval' => $this->interval,
            ],
        ];

        if ($this->dateFormat) {
            $data['format'] = $this->dateFormat;
        }

        return [$this->getAggregationName() => $data];
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

        $this->results = [];

        foreach ($raw['aggregations'][$name]['buckets'] as $bucket) {
            if ($bucket['doc_count']) {
                $this->results[$bucket['key_as_string']] = $bucket['doc_count'];
            }
        }
    }
}
