<?php

namespace Ucms\Search\Lucene;

class Query extends CollectionQuery
{
   /**
    * Require operator
    */
    const OP_REQUIRE = '+';

    /**
     * Prohibit operator
     */
    const OP_PROHIBIT = '-';

    /**
     * Boost operator
     */
    const OP_BOOST = '^';

    /**
     * Fuzzyness / roaming operator
     */
    const OP_FUZZY_ROAMING = "~";

    /**
     * And operator
     */
    const OP_AND = 'AND';

    /**
     * Or operator
     */
    const OP_OR = 'OR';

    /**
     * '*' wildcard
     */
    const WILDCARD_ALL = '*';

    /**
     * Create new term collection statement
     *
     * @return \Ucms\Search\Lucene\TermCollectionQuery
     */
    public function createTermCollection($operator = Query::OP_AND)
    {
        $statement = new TermCollectionQuery();
        $statement->setOperator($operator);

        $this->add($statement);

        return $statement;
    }

    /**
     * Create new term collection statement
     *
     * @return \Ucms\Search\Lucene\CollectionQuery
     */
    public function createCollection($operator = Query::OP_AND)
    {
        $statement = new CollectionQuery();
        $statement->setOperator($operator);

        $this->add($statement);

        return $statement;
    }

    /**
     * Create new term statement
     *
     * @return \Ucms\Search\Lucene\TermQuery
     */
    public function createTerm()
    {
        $statement = new TermQuery();

        $this->add($statement);

        return $statement;
    }

    /**
     * Create new arbitrary range statement
     *
     * @return \Ucms\Search\Lucene\RangeQuery
     */
    public function createRange()
    {
        $statement = new RangeQuery();

        $this->add($statement);

        return $statement;
    }

    /**
     * Create new arbitrary range statement
     *
     * @return \Ucms\Search\Lucene\DateRangeQuery
     */
    public function createDateRange()
    {
        $statement = new DateRangeQuery();

        $this->add($statement);

        return $statement;
    }

    /**
     * Match single term to this query
     *
     * @param string $field
     * @param string|\Ucms\Search\Lucene\TermQuery $term
     * @param float $boost
     * @param float $fuzzyness
     *
     * @return \Ucms\Search\Lucene\Query
     */
    public function matchTerm($field = null, $term, $boost = null, $fuzzyness = null)
    {
        $this
            ->createTerm()
            ->setValue($term)
            ->setFuzzyness($fuzzyness)
            ->setBoost($boost)
            ->setField($field)
        ;

        return $this;
    }

    /**
     * Require range
     *
     * @param string $field
     * @param mixed $start
     * @param mixed $stop
     * @param boolean $inclusive
     *
     * @return \Ucms\Search\Lucene\Query
     */
    public function requireRange($field = null, $start = null, $stop = null, $inclusive = true)
    {
        $this
            ->createRange()
            ->setField($field)
            ->setInclusive($inclusive)
            ->setRange($start, $stop)
        ;

        return $this;
    }

    /**
     * Require date range
     *
     * @param string $field
     * @param int|string|\DateTime $start
     *   Timestamp, \DateTime parsable string or \DateTime object
     * @param int|string|\DateTime $stop
     *   Timestamp, \DateTime parsable string or \DateTime object
     * @param boolean $inclusive
     *
     * @return \Ucms\Search\Query
     */
    public function requireDateRange($field = null, $start = null, $stop = null, $inclusive = true)
    {
        $this
            ->createDateRange()
            ->setInclusive($inclusive)
            ->setRange($start, $stop)
            ->setField($field)
        ;

        return $this;
    }

    /**
     * Require single term to this query
     *
     * @param string $field
     * @param string|\Ucms\Search\Lucene\TermQuery $term
     *
     * @return \Ucms\Search\Lucene\Query
     */
    public function requireTerm($field = null, $term)
    {
        $this
            ->createTerm()
            ->setValue($term)
            ->setExclusion(self::OP_REQUIRE)
            ->setField($field)
        ;

        return $this;
    }

    /**
     * Prohibit single term to this query
     *
     * @param string $field
     * @param string|\Ucms\Search\Lucene\TermQuery $term
     *
     * @return \Ucms\Search\Lucene\Query
     */
    public function prohibitTerm($field = null, $term)
    {
        $this
            ->createTerm()
            ->setValue($term)
            ->setField($field)
            ->setExclusion(self::OP_PROHIBIT)
        ;

        return $this;
    }

    /**
     * Match term collection (OR by default)
     *
     * @param string $field
     * @param string[]|\Ucms\Search\Lucene\TermQuery[] $terms
     * @param float $boost
     * @param string $operator
     *
     * @return \Ucms\Search\Lucene\Query
     */
    public function matchTermCollection($field = null, $terms, $boost = null, $operator = self::OP_OR)
    {
        if (!is_array($terms)) {
            $terms = [$terms];
        }

        $this
            ->createTermCollection()
            ->addAll($terms)
            ->setOperator($operator)
            ->setField($field)
            ->setBoost($boost)
        ;

        return $this;
    }

    /**
     * Require term collection (OR by default)
     *
     * @param string $field
     * @param string[]|\Ucms\Search\Lucene\TermQuery[] $terms
     * @param float $boost
     * @param string $operator
     *
     * @return \Ucms\Search\Lucene\Query
     */
    public function requireTermCollection($field = null, $terms, $operator = self::OP_OR)
    {
        if (!is_array($terms)) {
            $terms = [$terms];
        }

        $this
            ->createTermCollection()
            ->addAll($terms)
            ->setOperator($operator)
            ->setField($field)
            ->setExclusion(self::OP_REQUIRE)
        ;

        return $this;
    }
}
