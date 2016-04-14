<?php

namespace MakinaCorpus\Ucms\Search\Aggs;

use MakinaCorpus\Ucms\Search\Response;
use MakinaCorpus\Ucms\Search\Search;

/**
 * Represent an Elastic Search facet based upon the aggregations feature
 */
abstract class AbstractFacet implements AggInterface
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
     * @var string
     */
    private $parameterName;

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
     * @param string $parameterName
     *   If different from field name
     */
    public function __construct($field, $type, $operator = Query::OP_AND, $parameterName = null)
    {
        $this->field = $field;
        $this->type = $type;
        $this->operator = $operator;
        $this->parameterName = $parameterName ? $parameterName : $field;
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
     * @return AbstractFacet
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
     * Get parameter name
     *
     * @return string
     */
    public function getParameterName()
    {
        return $this->parameterName;
    }

    /**
     * Set currently user-selected facet values for query
     *
     * @param string[] $selectedValues
     *
     * @return AbstractFacet
     */
    public function setSelectedValues($selectedValues = [])
    {
        if (null === $selectedValues) {
            $selectedValues = [];
        }

        $this->selectedValues = $selectedValues;

        return $this;
    }

    /**
     * Get currently user-selected facet values for query
     *
     * Before query is sent, this will return programmatically set values
     * using the setSelectedValues() method, after query has been done, it
     * will return the selected values returned in the aggregation bucket
     *
     * @return string[]
     */
    public function getSelectedValues()
    {
        return $this->selectedValues;
    }

    /**
     * Get parameter in query
     *
     * @param string[] $query
     * @param string $param
     * @param mixed $default
     *
     * @return mixed
     *
     * @deprecated
     *   Use Symfony's Request instead
     */
    protected function getQueryParam($query, $param, $default = null)
    {
        if (array_key_exists($param, $query)) {
          return $query[$param];
        }

        return $default;
    }

    /**
     * {@inheritDoc}
     */
    public function prepareQuery(Search $search, $query)
    {
        $values = $this->getQueryParam($query, $this->getParameterName());
        if ($values) {
            if (!is_array($values)) {
                $values = [$values];
            }
            $this->setSelectedValues($values);
        }

        if ($values) {
            $search
                ->getFilterQuery()
                ->matchTermCollection(
                    $this->getField(),
                    $values,
                    null,
                    $this->getOperator()
                )
            ;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function buildQueryData(Search $search, $query)
    {
        return [
            $this->getParameterName() => [
                'terms' => [
                    'field' => $this->getField(),
                ],
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function parseResponse(Search $search, Response $response, $raw)
    {
    }
}
