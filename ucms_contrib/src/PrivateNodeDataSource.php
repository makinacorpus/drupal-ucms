<?php

namespace MakinaCorpus\Ucms\Contrib;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Ucms\Dashboard\Page\LinksFilterDisplay;
use MakinaCorpus\Ucms\Search\Aggs\TermFacet;
use MakinaCorpus\Ucms\Search\QueryAlteredSearch;
use MakinaCorpus\Ucms\Dashboard\Page\SortManager;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;

class PrivateNodeDataSource extends AbstractDatasource
{
    /**
     * @var QueryAlteredSearch
     */
    private $search;

    /**
     * Default constructor
     *
     * @param QueryAlteredSearch $search
     */
    public function __construct(QueryAlteredSearch $search)
    {
        $this->search = $search;
    }

    /**
     * @return TermFacet[]
     */
    private function createTermFacets()
    {
        $ret = [];

        $ret[] = $this
            ->search
            ->createTermAggregation('type', null)
            ->setChoicesMap(node_type_get_names())
            ->setTitle(t("Type"))
        ;

        $ret[] = $this
            ->search
            ->createTermAggregation('owner', null)
            ->setChoicesCallback(function ($values) {
                if ($accounts = user_load_multiple($values)) {
                    foreach ($accounts as $index => $account) {
                        $accounts[$index] = filter_xss(format_username($account));
                    }
                    return $accounts;
                }
            })
            ->setTitle(t("Owner"))
        ;

        $ret[] = $this
            ->search
            ->createTermAggregation('tags', null)
            ->setChoicesCallback(function ($values) {
                if ($terms = taxonomy_term_load_multiple($values)) {
                    foreach ($terms as $index => $term) {
                        $terms[$index] = check_plain($term->name);
                    }
                    return $terms;
                }
            })
            ->setTitle(t("Tags"))
        ;

        $ret[] = $this
            ->search
            ->createTermAggregation('status', null)
            ->setChoicesMap([0 => t("Unpublished"), 1 => t("Published")])
            ->setTitle(t("Status"))
        ;

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters($query)
    {
        $ret = [];

        // Apply rendering stuff for it to work
        foreach ($this->search->getAggregations() as $facet) {
            $ret[] = (new LinksFilterDisplay($facet->getField(), $facet->getTitle(), true))->setChoicesMap($facet->getFormattedChoices());
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function init($query)
    {
        $this->createTermFacets();
    }

    /**
     * {@inheritdoc}
     */
    public function getItems($query, PageState $pageState)
    {
        $response = $this
            ->search
            ->setPageParameter($pageState->getPageParameter())
            ->addField('_id')
            ->setLimit($pageState->getLimit())
            ->prepare($query)
            ->doSearch()
        ;

        $pageState->setTotalItemCount($response->getTotal());

        return node_load_multiple($response->getAllNodeIdentifiers());
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchFormParamName()
    {
        return $this->search->getFulltextParameterName();
    }
}
