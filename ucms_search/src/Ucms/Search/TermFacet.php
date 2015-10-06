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
     * @var callable
     */
    private $choicesCallback;

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
     * Set choices callback
     *
     * Works the same way as the choices map, but instead of giving a fixed
     * value map, the given callback should take a value as first parameter
     * and return the human readable value if found, null if not found.
     *
     * IMPORTANT: the callback MUST be able to get a parameter as parameter
     * case in which it should return a keyed array with values as keys and
     * values as titles, allowing to proceed to bulk operations for obvious
     * performance reasons.
     *
     * Please note that fixed map has always the priority over the callable.
     *
     * @param callable $choicesCallback
     *
     * @return \Ucms\Search\TermFacet
     */
    public function setChoicesCallback(callable $choicesCallback)
    {
        $this->choicesCallback = $choicesCallback;

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
            $title = null;

            if (isset($this->choicesMap[$value])) {
                $title = $this->choicesMap[$value];
            } else {
                // @todo Later optimisation allowing to run a multiple process
                // callback (for exemple user_load_multiple())... for
                // performances.
                if (is_callable($this->choicesCallback)) {
                    $title = call_user_func($this->choicesCallback, $value);
                }
            }

            // Default fallback on indexed value
            if (!$title) {
                $title = $value;
            }

            $ret[$value] = $title . " (". $count .")";
        }

        return $ret;
    }
}
