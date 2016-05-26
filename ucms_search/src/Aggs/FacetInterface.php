<?php

namespace MakinaCorpus\Ucms\Search\Aggs;

/**
 * A facet is something using an aggregation, but providing a few pure UX
 * helpers, in order to help you to build facets around aggregations
 */
interface FacetInterface extends AggInterface
{
    /**
     * Get formatter list of choices after query
     *
     * @return string[]
     */
    public function getFormattedChoices();
}
