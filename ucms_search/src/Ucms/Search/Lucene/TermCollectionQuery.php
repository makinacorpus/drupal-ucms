<?php

namespace Ucms\Search\Lucene;

/**
 * Represent a user term collection
 */
class TermCollectionQuery extends CollectionQuery
{
    /**
     * Add a term
     *
     * @param string|\Ucms\Search\Lucene\TermQuery $element
     *
     * @return \Ucms\Search\Lucene\TermCollectionQuery
     */
    public function add($element)
    {
        if ($element instanceof TermQuery) {
            parent::add($element);
        } else {
            parent::add(
                (new TermQuery())
                    ->setValue($element)
            );
        }

        return $this;
    }

    /**
     * Add a list of terms
     *
     * @param string[]|\Ucms\Search\Lucene\TermQuery[] $terms
     *
     * @return \Ucms\Search\Lucene\TermCollectionQuery
     */
    public function addAll(array $terms)
    {
        foreach ($terms as $term) {
            $this->add($term);
        }

        return $this;
    }
}