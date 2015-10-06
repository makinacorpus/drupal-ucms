<?php

namespace Ucms\Search;

/**
 * Represent an Elastic Search facet based upon the aggregations feature
 */
abstract class AbstractFacet
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $operator;

    /**
     * @var string[]
     */
    private $selectedValues = [];

    /**
     * @var string
     */
    private $type = 'terms';

    /**
     * Default constructor
     *
     * @param string $field
     *   Field name
     * @param string $type
     *   Aggregation type
     * @param string $operator
     *   Query::OP_AND or Query::OP_OR determines how is built the Lucene
     *   aggregation query and how the facet values should operate on the
     *   search query
     */
    public function __construct($field, $type, $operator = Query::OP_AND)
    {
        $this->field = $field;
        $this->type = $type;
        $this->operator = $operator;
    }

    /**
     * Get aggregation type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get aggregation query operator
     *
     * @return string
     *   Query::OP_AND or Query::OP_OR
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * Query aggregation field
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Set human name title for end-user display
     *
     * @param string $title
     *
     * @return \Ucms\Search\AbstractFacet
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get human name title for end-user display
     *
     * If none set return the aggregation field name
     *
     * @return string
     */
    public function getTitle()
    {
        if ($this->title) {
            return $this->title;
        } else {
            return $this->field;
        }
    }

    /**
     * Set currently user-selected facet values for query
     *
     * @param string[] $selectedValues
     *
     * @return \Ucms\Search\AbstractFacet
     */
    public function setSelectedValue($selectedValues = [])
    {
        $this->selectedValues = $selectedValues;

        return $this;
    }

    /**
     * Get currently user-selected facet values for query
     *
     * Before query is sent, this will return programmatically set values
     * using the setSelectedValue() method, after query has been done, it
     * will return the selected values returned in the aggregation bucket
     *
     * @return \Ucms\Search\string[]
     */
    public function getSelectedValues()
    {
        return $this->selectedValues;
    }
}
