<?php

namespace Ucms\Search;

class Response
{
    /**
     * @var int
     */
    protected $limit;

    /**
     * @var int
     */
    protected $page;

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
     * @param array $rawResponse
     *   \Elasticsearch\Client::search() method return
     */
    public function __construct($rawResponse, $limit = UCMS_SEARCH_LIMIT, $page = 1)
    {
        $this->rawResponse = $rawResponse;
        $this->limit = $limit;
        $this->page = $page;
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
        return $this->limit;
    }

    /**
     * Get current request limit
     *
     * @return int
     */
    public function getPage()
    {
        return $this->page;
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
}
