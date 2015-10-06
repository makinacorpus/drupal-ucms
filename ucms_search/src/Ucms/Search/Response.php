<?php

namespace Ucms\Search;

class Response
{
    /**
     * @var \Ucms\Search\Search
     */
    protected $search;

    /**
     * @var array
     */
    protected $rawResponse;

    /**
     * @var boolean
     */
    protected $isSuccessful = false;

    /**
     * Default constructor
     *
     * @param \Ucms\Search\Search $search
     * @param array $rawResponse
     *   \Elasticsearch\Client::search() method return
     */
    public function __construct(Search $search, $rawResponse)
    {
        $this->search = $search;
        $this->rawResponse = $rawResponse;
        $this->isSuccessful = !empty($rawResponse['_shards']) && count($rawResponse['_shards']['successful']);
    }

    /**
     * Is this request successful
     *
     * @return boolean
     */
    public function isSuccessful()
    {
        return $this->isSuccessful;
    }

    /**
     * Get current request limit
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->search->getLimit();
    }

    /**
     * Get current request limit
     *
     * @return int
     */
    public function getPage()
    {
        return $this->search->getPage();
    }

    /**
     * Get current request total count
     *
     * @return int
     */
    public function getTotal()
    {
        return $this->isSuccessful ? (int)$this->rawResponse['hits']['total'] : 0;
    }

    /**
     * Aggregate all node identifiers and return the array
     *
     * @return int[]
     */
    public function getAllNodeIdentifiers()
    {
        $ret = [];

        if ($this->isSuccessful) {
            foreach ($this->rawResponse['hits']['hits'] as $document) {
                $ret[] = $document['_id'];
            }
        }

        return $ret;
    }

    /**
     * Get all term aggregations results
     *
     * @return array
     *   First dimension keys are the aggregation name provided to the
     *   Search::addTermAggregation() method, values are maps of result
     *   counts keyed by the field value
     */
    public function getTermAggregations()
    {
        $ret = [];

        foreach ($this->search->getAggregations() as $name => $data) {
            if ('terms' === $data['type']) {

                if (!isset($this->rawResponse['aggregations'][$name])) {
                    throw new \RuntimeException(sprintf("Aggregation '%s' is missing from response", $name));
                }

                foreach ($this->rawResponse['aggregations'][$name]['buckets'] as $bucket) {
                    $ret[$name][$bucket['key']] = $bucket['doc_count'];
                }
            }
        }

        return $ret;
    }
}
