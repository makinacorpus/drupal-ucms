<?php

namespace MakinaCorpus\Ucms\Search\Aggs;

use MakinaCorpus\Ucms\Search\Lucene\Query;
use MakinaCorpus\Ucms\Search\Search;
use MakinaCorpus\Ucms\Search\Response;

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
     * @var bool
     */
    private $exclusiveMode = false;

    /**
     * Default constructor
     *
     * @param string $field
     *   Field name
     * @param string $operator
     *   Query::OP_AND or Query::OP_OR determines how is built the Lucene
     *   aggregation query and how the facet values should operate on the
     *   search query
     * @param string $parameterName
     *   Parameter name if different from field
     */
    public function __construct($field, $operator = Query::OP_AND, $parameterName = null)
    {
        parent::__construct($field, 'terms', $operator, $parameterName);
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
     * @return TermFacet
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
     * @return TermFacet
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
     * @return TermFacet
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
     * @return TermFacet
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
        $ret    = [];
        $loaded = [];

        // Populate the raw formatted array with just values as values
        foreach (array_keys($this->choices) as $value) {
            $ret[$value] = $value;
        }

        // First start with arbitrarily set choices map
        if ($this->choicesMap) {
            $loaded = array_intersect_key($this->choicesMap, $ret);
        }

        // Execute the user given callback
        if ($this->choicesCallback) {

              $callbackLoaded = call_user_func(
                  $this->choicesCallback,
                  // Exclude already loaded items to leave the choices map
                  // having precedence over the callback
                  array_diff_key($ret, $loaded)
              );

              // We are forced to proceed to a two step merge (using foreach)
              // else array_merge() as well as the + operator would terribly
              // fail merging integer keys
              if ($callbackLoaded) {
                  foreach ($callbackLoaded as $value => $title) {
                      $loaded[$value] = $title;
                  }
              }
        }

        // Append the count for each value
        foreach ($this->choices as $value => $count) {
            $append = ' <span class="badge">' . $count . '</span>';
            if (isset($loaded[$value])) {
                $ret[$value] = $loaded[$value] . $append;
            } elseif(!$this->exclusiveMode) {
                $ret[$value] .= $append;
            }
            else {
                unset($ret[$value]);
            }
        }

        return $ret;
    }

    /**
     * @param boolean $exclusiveMode
     * @return $this
     */
    public function setExclusive($exclusiveMode)
    {
        $this->exclusiveMode = $exclusiveMode;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function parseResponse(Search $search, Response $response, $raw)
    {
        $choices = [];

        $name = $this->getParameterName();

        if (!isset($raw['aggregations'][$name])) {
            throw new \RuntimeException(sprintf("Aggregation '%s' is missing from response", $name));
        }

        foreach ($raw['aggregations'][$name]['buckets'] as $bucket) {
            $choices[$bucket['key']] = $bucket['doc_count'];
        }

        $this->setChoices($choices);
    }
}
