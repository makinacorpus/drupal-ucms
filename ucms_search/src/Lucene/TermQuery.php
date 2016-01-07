<?php

namespace MakinaCorpus\Ucms\Search\Lucene;

/**
 * Represent a simple user term or phrase
 */
class TermQuery extends AbstractFuzzyQuery
{
    /**
     * Term
     */
    protected $term = null;

    /**
     * Set term
     *
     * @param string $term
     *
     * @return TermQuery
     */
    public function setValue($term)
    {
        $this->term = trim((string)$term);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function toRawString()
    {
        return self::escapeToken($this->term);
    }
}
