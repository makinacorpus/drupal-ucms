<?php

namespace MakinaCorpus\Ucms\Search\Aggs;

use MakinaCorpus\Ucms\Search\Lucene\Query;
use MakinaCorpus\Ucms\Search\Response;
use MakinaCorpus\Ucms\Search\Search;

/**
 * Produce a facet using a date histogram aggregation
 */
class DateHistogramFacet extends AbstractFacet implements AggInterface
{
    private $aggregation;

    /**
     * Default constructor
     *
     * @param string $field
     *   Field name
     * @param string $interval
     *   One of these object constants or a date format string
     */
    public function __construct($field, $operator = Query::OP_AND, $parameterName = null, $isPostFilter = false)
    {
        parent::__construct($field, $operator, $parameterName, $isPostFilter);

        $this->aggregation = new DateHistogram($field);
    }

    /**
     * Get aggregation type
     *
     * @return string
     */
    public function getType()
    {
        return 'date_histogram';
    }

    /**
     * Get aggregation name
     *
     * @return string
     */
    public function getAggregationName()
    {
        return $this->aggregation->getAggregationName();
    }

    /**
     * {@inheritDoc}
     */
    public function prepareQuery(Search $search, $query)
    {
        parent::prepareQuery($search, $query);

        return $this->aggregation->prepareQuery($search, $query);
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
        return $this->aggregation->getResults();
    }

    /**
     * {@inheritdoc}
     */
    public function getFormattedChoices()
    {
        $ret = [];

        $interval = $this->aggregation->getInterval();
        $results = $this->getResults();

        krsort($results);

        foreach ($results as $key => $count) {
            switch ($interval) {

                case DateHistogram::SECOND:
                case DateHistogram::MINUTE:
                case DateHistogram::HOUR:
                case DateHistogram::DAY:
                case DateHistogram::WEEK:
                case DateHistogram::MONTH:
                case DateHistogram::QUARTER:
                case DateHistogram::YEAR:
                    // FIXME.
                    $now = new \DateTime("this month");
                    $ref = new \DateTime($key);
                    $text = format_interval($now->getTimestamp() - $ref->getTimestamp());
                    break;

            }

            $ret[$key] = $text . ' <span class="badge">' . $count . '</span>';
        }

        return $ret;
    }

    /**
     * {@inheritDoc}
     */
    public function buildQueryData(Search $search, $query)
    {
        return $this->aggregation->buildQueryData($search, $query);
    }

    /**
     * {@inheritDoc}
     */
    public function parseResponse(Search $search, Response $response, $raw)
    {
        return $this->aggregation->parseResponse($search, $response, $raw);
    }
}
