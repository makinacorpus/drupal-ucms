<?php

namespace MakinaCorpus\Ucms\Search;

use Elasticsearch\Client;

use MakinaCorpus\Ucms\Search\Aggs\TermFacet;
use MakinaCorpus\Ucms\Search\Aggs\TopHits;
use MakinaCorpus\Ucms\Search\Lucene\Query;
use MakinaCorpus\Ucms\Search\Sort\Sort;

class Search
{
    /**
     * Default fulltext query parameter name
     */
    const PARAM_FULLTEXT_QUERY = 's';

    /**
     * Page parameter name
     */
    const PARAM_PAGE = 'page';

    /**
     * Default fulltext search romain and fuziness value
     */
    const DEFAULT_ROAMING = 0.8;

    /**
     * @var \Elasticsearch\Client
     */
    private $client;

    /**
     * @var string
     */
    private $index;

    /**
     * @var Query
     */
    private $query;

    /**
     * @var Query
     */
    private $filterQuery;

    /**
     * @var int
     */
    private $limit = UCMS_SEARCH_LIMIT;

    /**
     * @var int
     */
    private $page = 1;

    /**
     * @var string[]
     */
    private $fields = [];

    /**
     * @var Sort[]
     */
    private $sortFields = [];

    /**
     * @var Aggs\AggInterface[]
     */
    private $aggregations = [];

    /**
     * @var string
     */
    private $fulltextParameterName = self::PARAM_FULLTEXT_QUERY;

    /**
     * @var string
     */
    private $pageParameterName = self::PARAM_PAGE;

    /**
     * Drupal paging start with 0, Elastic search one starts with 1
     *
     * @var int
     */
    private $pageDelta = 1;

    /**
     * @var float
     */
    private $fulltextRoaming = self::DEFAULT_ROAMING;

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
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function setIndex($index)
    {
        $this->index = $index;

        return $this;
    }

    /**
     * Get query
     *
     * @return $this
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get query
     *
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function addField($field)
    {
        if (!in_array($field, $this->fields)) {
            $this->fields[] = $field;
        }

        return $this;
    }

    /**
     * Set fulltext query parameter name
     *
     * @param string $parameterName
     *
     * @return $this
     */
    public function setFulltextParameterName($parameterName)
    {
        $this->fulltextParameterName = $parameterName;

        return $this;
    }

    /**
     * Get fulltext query parameter name
     *
     * @return string
     */
    public function getFulltextParameterName()
    {
        return $this->fulltextParameterName;
    }

    /**
     * Set fulltext roaming and fuziness value, should be between 0 and 1
     *
     * @param float $value
     *
     * @return $this
     */
    public function setFulltextRoaming($value)
    {
        $this->fulltextRoaming = (float)$value;

        return $this;
    }

    /**
     * Set page delta
     *
     * @param int $value
     *
     * @return $this
     */
    public function setPageDelta($value)
    {
        $this->pageDelta = (int)$value;

        return $this;
    }

    /**
     * Set page parameter name
     *
     * @param string $parameterName
     *
     * @return $this
     */
    public function setPageParameter($parameterName)
    {
        $this->pageParameterName = (string)$parameterName;

        return $this;
    }
    

    /**
     * Add "top hits" aggregation
     *
     * @param string $field
     *   Field to build buckets with
     * @param int $sire
     *   Number of items per bucket
     *
     * @return TopHits
     */
    public function createTopHits($field, $size = 1)
    {
        return $this->aggregations[] = new TopHits($field, $size);
    }

    /**
     * Add facet aggregation
     *
     * @param string $field
     *   Field name if different from the name
     * @param mixed[] $currentValues
     *   Current values for filtering if any
     * @param boolean $filter
     *   Filter aggregation will filter the result before running the
     *   aggregation while global aggregation will always return values
     *   for the whole index priori to filtering
     * @param string $operator
     *   Operator to use for filtering (Query::OP_OR or Query::OP_AND)
     * @param string $parameterName
     *   Facet query parameter name if different from field name
     *
     * @return TermFacet
     */
    public function createFacet($field, $values = null, $operator = Query::OP_OR, $parameterName = null)
    {
        return $this->aggregations[] = (new TermFacet($field, $operator, $parameterName))->setSelectedValues($values);
    }

    /**
     * @deprecated
     *   Use Symfony's Request instead
     */
    private function getQueryParam($query, $param, $default = null)
    {
        if (array_key_exists($param, $query)) {
            return $query[$param];
        }

        return $default;
    }

    /**
     * Get aggregations
     *
     * @return Aggs\AggInterface[]
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * Add sort
     *
     * @param string $sortField
     * @param string $sortOrder
     *      'asc' or 'desc'
     */
    public function addSort($sortField, $sortOrder = 'asc')
    {
        $this->sortFields[] = new Sort($sortField, $sortOrder);
    }

    /**
     * @param \array[] $sortFields
     * @return Search
     */
    public function setSortFields($sortFields)
    {
        $this->sortFields = $sortFields;

        return $this;
    }

    /**
     * Build aggregations query data
     *
     * @return string[]
     */
    private function buildAggQueryData($query)
    {
        $ret = [];

        foreach ($this->aggregations as $agg) {
            $additions = $agg->buildQueryData($this, $query);
            if ($additions) {
                $ret = array_merge($ret, $additions);
            }
        }

        return $ret;
    }

    /**
     * Prepare current search using the incomming query
     *
     * @param string[] $query
     */
    private function prepare($query)
    {
        foreach ($this->aggregations as $agg) {
            $agg->prepareQuery($this, $query);
        }

        // Handle paging
        $this->setPage(
            $this->getQueryParam($query, $this->pageParameterName, 0)
                + $this->pageDelta
        );

        // Only process query when there is a value, in order to avoid sending
        // an empty query string to ElasticSearch, its API is so weird that we
        // probably would end up with exceptions
        $value = $this->getQueryParam($query, $this->fulltextParameterName);
        if ($value) {
            $this
                ->getQuery()
                ->matchTerm(
                    'combined',
                    $value,
                    null,
                    $this->fulltextRoaming
                )
            ;
        }
    }

    /**
     * Run the search and return the response
     *
     * @param string[] $query
     *   Incomming query
     */
    public function doSearch($query = [])
    {
        if (!$this->index) {
            throw new \RuntimeException("You must set an index");
        }

        $this->prepare($query);

        $isQueryEmpty = !count($this->query);

        // This must be set before filter since filter query will be altered by
        // the applied aggregations
        $aggs = $this->buildAggQueryData($query);

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
/*
        if ($this->fields) {
            $body['fields'] = $this->fields;
        }*/

        if (count($this->sortFields)) {
            foreach ($this->sortFields as $sort) {
                $body['sort'][] = $sort->getSortStructure();
            }
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
