<?php

namespace MakinaCorpus\Ucms\Search\Aggs;

use MakinaCorpus\Ucms\Search\Response;
use MakinaCorpus\Ucms\Search\Search;

/**
 * Represent an Elastic Search top-hits aggregation
 *
 * Top hits aggregations are supposed to be sub-aggregations, our API is rather
 * stupid, reason why we are going to make it being "top level" and it will
 * embed itself in a term query. We'll see later if we need something more
 * accurate.
 *
 * https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-top-hits-aggregation.html
 */
class TopHits implements AggInterface
{
    /**
     * @var string
     */
    private $field;

    /**
     * @var int
     */
    private $size;

    /**
     * @var int[][]
     */
    private $results;

    /**
     * Default constructor
     *
     * @param string $field
     *   Field name
     * @param int $type
     *   Number of items to fetch per bucket
     */
    public function __construct($field, $size = 1)
    {
        $this->field = $field;
        $this->size = $size;
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
        return "top_" . $this->field;
    }

    /**
     * Return in-bucket hits array name
     *
     * @return string
     */
    public function getAggregationBucketName()
    {
        return 'top_' . $this->field . '_hits';
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
        return [
            $this->getAggregationName() => [
                "terms" => [
                    "field" => $this->field,
                    "size" => $this->size,
                ],
                "aggs" => [
                     $this->getAggregationBucketName() => [
                        "top_hits" => [
                            /*"sort" => [ // @todo Should we handle sort? default is score
                                [
                                    "created" => [
                                        "order" => "desc",
                                    ],
                                ],
                            ],*/
                            "_source" => [
                                "include" => [
                                    "title", // @todo this is arbitrary, should we do something else?
                                ]
                            ],
                            "size" => $this->size, // @todo WTF size per bucket?
                        ],
                    ],
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

        $this->results = [];

        foreach ($raw['aggregations'][$name]['buckets'] as $bucket) {
            foreach ($bucket[$this->getAggregationBucketName()]['hits']['hits'] as $data) {
                $this->results[$bucket['key']][] = $data['_id'];
            }
        }
    }
}
