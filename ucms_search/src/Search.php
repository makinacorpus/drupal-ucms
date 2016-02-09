<?php

namespace MakinaCorpus\Ucms\Search;

use Elasticsearch\Client;

use MakinaCorpus\Ucms\Search\Aggs\AbstractFacet;
use MakinaCorpus\Ucms\Search\Aggs\TermFacet;
use MakinaCorpus\Ucms\Search\Lucene\Query;

class Search
{
    /**
     * @var \Elasticsearch\Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $index;

    /**
     * @var Query
     */
    protected $query;

    /**
     * @var Query
     */
    protected $filterQuery;

    /**
     * @var int
     */
    protected $limit = UCMS_SEARCH_LIMIT;

    /**
     * @var int
     */
    protected $page = 1;

    /**
     * @var string[]
     */
    protected $fields = [];

    /**
     * @var Aggs\AbstractFacet[]
     */
    protected $aggregations = [];

    /**
     * Default constructor
     *
     * @param \Elasticsearch\Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->query = new Query();
        $this->filterQuery = (new Query())->setOperator(Query::OP_AND);
    }

    /**
     * Set limit
     *
     * @param int $limit
     *   Positive integer or null if no limit
     *
     * @return Search
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Get current limit
     *
     * @return int
     *   Positive integer or null if no limit
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Set page
     *
     * @param int $page
     *   Positive integer or null or 1 for first page
     *
     * @return Search
     */
    public function setPage($page)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * Get current page
     *
     * @return int
     *   Positive integer or null if no limit
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Set index
     *
     * @param string $index
     *
     * @return Search
     */
    public function setIndex($index)
    {
        $this->index = $index;

        return $this;
    }

    /**
     * Get query
     *
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get query
     *
     * @return Query
     */
    public function getFilterQuery()
    {
        return $this->filterQuery;
    }

    /**
     * Set returned fields
     *
     * @param string[]
     *
     * @return Search
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Add field to returned fields
     *
     * @param string $field
     *
     * @return Search
     */
    public function addField($field)
    {
        if (!in_array($field, $this->fields)) {
            $this->fields[] = $field;
        }

        return $this;
    }

    /**
     * Add facet aggregation
     *
     * @param string $parameterName
     *   Facet query parameter name, if field name is not specified it will
     *   also be used a facet field name
     * @param mixed[] $currentValues
     *   Current values for filtering if any
     * @param boolean $filter
     *   Filter aggregation will filter the result before running the
     *   aggregation while global aggregation will always return values
     *   for the whole index priori to filtering
     * @param string $operator
     *   Operator to use for filtering (Query::OP_OR or Query::OP_AND)
     * @param string $field
     *   Field name if different from the name
     *
     * @return TermFacet
     */
    public function createTermAggregation($parameterName, $values = null, $operator = Query::OP_OR, $field = null)
    {
        if (!$field) {
            $field = $parameterName;
        }

        $facet = (new TermFacet($field, $operator))
          ->setSelectedValues($values)
        ;

        $this->aggregations[$parameterName] = $facet;

        return $facet;
    }

    /**
     * Build aggregations from current data
     *
     * @return array
     *   Aggregation data
     */
    protected function applyAggregations()
    {
        $ret = [];

        foreach ($this->aggregations as $parameterName => $facet) {
            $values = $facet->getSelectedValues();

            if ($values) {
                $this
                    ->getFilterQuery()
                    ->matchTermCollection(
                        $facet->getField(),
                        $values,
                        null,
                        $facet->getOperator()
                    )
                ;
            }

            $ret[$parameterName] = [
                'terms' => [
                    'field' => $facet->getField(),
                ],
            ];
        }

        return $ret;
    }

    /**
     * Get aggregations
     *
     * @return AbstractFacet[]
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * Run the search and return the response
     */
    public function doSearch()
    {
        if (!$this->index) {
            throw new \RuntimeException("You must set an index");
        }

        $isQueryEmpty = !count($this->query);

        // This must be set before filter since filter query will be altered by
        // the applied aggregations
        $aggs = $this->applyAggregations();

        if (count($this->filterQuery)) {
            if ($isQueryEmpty) {
                $body = [
                    'query' => [
                        'constant_score' => [
                            'filter' => [
                                'fquery' => [
                                    'query' => [
                                        'query_string' => [
                                            'query' => (string)$this->filterQuery
                                        ],
                                    ],
                                    // @todo Without this ElasticSearch seems to
                                    // throw exceptions...
                                    '_cache' => true,
                                ],
                            ],
                        ],
                    ],
                ];
            } else {
                $body = [
                    'query' => [
                        'filtered' => [
                            'query'  => [
                               'query_string' => [
                                   'query' => (string)$this->query
                               ],
                            ],
                            'filter' => [
                                'fquery' => [
                                    'query' => [
                                        'query_string' => [
                                            'query' => (string)$this->filterQuery
                                        ],
                                    ],
                                    // @todo Without this ElasticSearch seems to
                                    // throw exceptions...
                                    '_cache' => true,
                                ],
                            ],
                        ],
                    ],
                ];
            }
        } else {
            if ($isQueryEmpty) {
                $body = [
                    'query' => [
                        'match_all' => []
                    ],
                ];
            } else {
                $body = [
                    'query' => [
                        'query_string' => [
                            'query' => (string)$this->query,
                        ]
                    ],
                ];
            }
        }

        if ($aggs) {
            $body['aggs'] = $aggs;
        }

        if ($this->fields) {
            $body['fields'] = $this->fields;
        }

        $data = [
            'index' => $this->index,
            'type'  => 'node',
            'body'  => $body,
        ];

        if (!empty($this->limit)) {
            $data['size'] = $this->limit;
            if (!empty($this->page)) {
                $data['from'] = max([0, $this->page - 1]) * $this->limit;
            }
        }

        return new Response($this, $this->client->search($data));
    }
}
