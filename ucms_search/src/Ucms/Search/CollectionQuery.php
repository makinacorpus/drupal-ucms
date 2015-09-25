<?php

namespace Ucms\Search;

class CollectionQuery extends AbstractQuery implements
    \IteratorAggregate,
    \Countable
{
    /**
     * @var \Ucms\Search\AbstractQuery[]
     */
    protected $elements = array();

    /**
     * Can be Query::OP_AND or Query::OP_OR, or null for default (AND)
     *
     * @var string
     */
    protected $operator = null;

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->elements);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->elements);
    }

    /**
     * Adds an element to the internal list
     *
     * @param \Ucms\Search\AbstractQuery $element
     *
     * @return \Ucms\Search\CollectionQuery
     */
    public function add($element)
    {
        if (!$element instanceof AbstractQuery) {
            throw new \InvalidArgumentException("Provided element is not an AbstractQuery instance");
        }

        $this->elements[] = $element;

        return $this;
    }

    /**
     * Get textual operator
     *
     * @return string
     *   Textual operator, surrounded by whitespaces or only one whitespace in
     *   case operator is not set (default behavior is to use default query
     *   operator specified in Solr params).
     */
    protected function getTextualOperator()
    {
        return ($this->operator ? (' ' . $this->operator . ' ') : ' ');
    }

    /**
     * Remove element
     *
     * @param \Ucms\Search\AbstractQuery $element
     *
     * @return \Ucms\Search\CollectionQuery
     */
    protected function removeElement(AbstractQuery $element)
    {
        foreach ($this->elements as $key => $existing) {
            if ($existing === $element) {
                unset($this->elements[$key]);
            }
        }

        return $this;
    }

    /**
     * Set default operator
     *
     * @param string $operator
     *
     * @return \Ucms\Search\CollectionQuery
     */
    public function setOperator($operator)
    {
        if ($operator == null || $operator == Query::OP_AND || $operator == Query::OP_OR) {
            $this->operator = $operator;
        } else {
            throw new \InvalidArgumentException("Operator must be Query::OP_AND or Query::OP_OR");
        }

        return $this;
    }

    /**
     * Get current operator.
     *
     * @return string $operator
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * {@inheritdoc}
     */
    protected function toRawString()
    {
        if (empty($this->elements)) {
            return '';
        }
        if (count($this->elements) > 1) {
            return '(' . implode($this->getTextualOperator() , $this->elements) . ')';
        } else {
            reset($this->elements);
            return (string)current($this->elements);
        }
    }
}