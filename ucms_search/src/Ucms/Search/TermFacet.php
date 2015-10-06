<?php

namespace Ucms\Search;

/**
 * Represent an Elastic Search facet based upon the aggregations feature
 */
class TermFacet extends AbstractFacet
{
    /**
     * @var string[]
     */
    private $valueFilter = [];

    /**
     * @var string[]
     */
    private $choices = [];

    /**
     * @var string
     */
    private $choicesMap = [];

    /**
     * Default constructor
     *
     * @param string $field
     *   Field name
     * @param string $operator
     *   Query::OP_AND or Query::OP_OR determines how is built the Lucene
     *   aggregation query and how the facet values should operate on the
     *   search query
     */
    public function __construct($field, $operator = Query::OP_AND)
    {
        parent::__construct($field, 'terms', $operator);
    }

    /**
     * Set value filter
     *
     * Value filter allows the caller to programmatically opt-out values
     * from the aggregation, allowing to refine the end-user UI but has no
     * effect on the query itself.
     *
     * @param string[] $valueFilter
     *
     * @return \Ucms\Search\TermFacet
     */
    public function setValueFilter($valueFilter)
    {
        $this->valueFilter = $valueFilter;

        return $this;
    }

    /**
     * Get value filter
     *
     * @return string[]
     */
    public function getValueFilter()
    {
        return $this->valueFilter;
    }

    /**
     * Set choices map
     *
     * Choice map is a key-value array in which keys are indexed values and
     * values are human readable names that will supplant the indexed values
     * for end-user display, this has no effect on the query.
     *
     * @param string[] $choicesMap
     *
     * @return \Ucms\Search\TermFacet
     */
    public function setChoicesMap($choicesMap)
    {
        $this->choicesMap = $choicesMap;

        return $this;
    }

    /**
     * Set choices
     *
     * This in an internal function that may only be called after query has
     * been done, it serves the purpose of given this instance the current
     * search context state; the given parameter is an key-value array where
     * keys are indexed values and values are the bucket returned term count.
     *
     * @param string $choices
     *
     * @return \Ucms\Search\TermFacet
     */
    public function setChoices($choices)
    {
        $this->choices = $choices;

        return $this;
    }

    /**
     * Get formatter list of choices after query
     *
     * @return string[]
     */
    public function getFormattedChoices()
    {
        $ret = [];

        foreach ($this->choices as $value => $count) {
            if (isset($this->choicesMap[$value])) {
                $ret[$value] = $this->choicesMap[$value] . " (". $count .")";
            } else {
                $ret[$value] = $value . " (". $count .")";
            }
        }

        return $ret;
    }
}
