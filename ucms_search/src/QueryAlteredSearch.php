<?php

namespace MakinaCorpus\Ucms\Search;

/**
 * Represent a search object based upon an incomming query, allowing
 * automation of facet value population and other stuff like dat.
 *
 * Note that for the page parameter, this may only work when a single
 * pager is on the page (else Drupal will array-ify the parameter and
 * we don't handle that).
 */
class QueryAlteredSearch extends Search
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
     * Set fulltext query parameter name
     *
     * @param string $parameterName
     *
     * @return QueryAlteredSearch
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
     * @return QueryAlteredSearch
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
     * @return QueryAlteredSearch
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
     * @return QueryAlteredSearch
     */
    public function setPageParameter($parameterName)
    {
        $this->pageParameterName = (string)$parameterName;

        return $this;
    }

    /**
     * Get parameter in query
     *
     * @param string[] $query
     * @param string $param
     * @param mixed $default
     *
     * @return mixed
     */
    protected function getQueryParam($query, $param, $default = null)
    {
        if (array_key_exists($param, $query)) {
            return $query[$param];
        }

        return $default;
    }

    /**
     * Prepare current search using the incomming query
     *
     * @param string[] $query
     *
     * @return QueryAlteredSearch
     */
    public function prepare($query)
    {
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

        // Process all facets
        foreach ($this->getAggregations() as $parameterName => $facet) {
            $values = $this->getQueryParam($query, $parameterName);
            if ($values) {
                if (is_array($values)) {
                    $facet->setSelectedValues($values);
                } else {
                    $facet->setSelectedValues([$values]);
                }
            }
        }

        return $this;
    }
}
