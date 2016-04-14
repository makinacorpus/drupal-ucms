<?php

namespace MakinaCorpus\Ucms\Search\Aggs;

use MakinaCorpus\Ucms\Search\Response;
use MakinaCorpus\Ucms\Search\Search;

/**
 * Implement this, and you aggregation will be usable, mostly
 */
interface AggInterface
{
    /**
     * Prepare query before building elastic data
     *
     * @param Search $search
     * @param string[] $query
     */
    public function prepareQuery(Search $search, $query);

    /**
     * Build this aggregation elastic data array
     *
     * @param Search $search
     * @param string[] $query
     *
     * @return string[][]...
     */
    public function buildQueryData(Search $search, $query);

    /**
     * Parse server response
     *
     * @param Search $search
     * @param Response $response
     * @param string[][]... $raw
     *   Raw server response
     */
    public function parseResponse(Search $search, Response $response, $raw);
}
