<?php

namespace MakinaCorpus\Ucms\Search;

use MakinaCorpus\Ucms\Search\Aggs\TermFacet;

class Response
{
    /**
     * @var Search
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
     * @param Search $search
     * @param array $rawResponse
     *   \Elasticsearch\Client::search() method return
     */
    public function __construct(Search $search, $rawResponse)
    {
        $this->search = $search;
        $this->rawResponse = $rawResponse;
        $this->isSuccessful = !empty($rawResponse['_shards']) && count($rawResponse['_shards']['successful']);
        $this->parseAggregations();
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
     * Parse aggregation results and update the current facet instances states 
     */
    protected function parseAggregations()
    {
        if (!$this->isSuccessful()) {
            return;
        }

        foreach ($this->search->getAggregations() as $name => $facet) {
            if ($facet instanceof TermFacet) {

                $choices = [];

                if (!isset($this->rawResponse['aggregations'][$name])) {
                    throw new \RuntimeException(sprintf("Aggregation '%s' is missing from response", $name));
                }

                foreach ($this->rawResponse['aggregations'][$name]['buckets'] as $bucket) {
                    $choices[$bucket['key']] = $bucket['doc_count'];
                }

                $facet->setChoices($choices);
            }
        }
    }
}
