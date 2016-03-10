<?php

namespace MakinaCorpus\Ucms\Contrib;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Ucms\Dashboard\Page\LinksFilterDisplay;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Dashboard\Page\SortManager;
use MakinaCorpus\Ucms\Search\Aggs\TermFacet;
use MakinaCorpus\Ucms\Search\QueryAlteredSearch;
use MakinaCorpus\Ucms\Search\SearchFactory;
use MakinaCorpus\Ucms\Site\SiteManager;

class PrivateNodeDataSource extends AbstractDatasource
{
    use StringTranslationTrait;

    /**
     * @var QueryAlteredSearch
     */
    private $search;

    /**
     * @var SiteManager
     */
    private $manager;

    /**
     * @var AccountInterface
     */
    private $account;

    /**
     * Default constructor
     *
     * @param SearchFactory $searchFactory
     * @param SiteManager $manager
     * @param AccountInterface $account
     * @param string $index
     */
    public function __construct(SearchFactory $searchFactory, SiteManager $manager, AccountInterface $account, $index = 'private')
    {
        $this->search = $searchFactory->create($index);
        $this->manager = $manager;
        $this->account = $account;
    }

    /**
     * Get datasource search object
     *
     * @return QueryAlteredSearch
     */
    public function getSearch()
    {
        return $this->search;
    }

    /**
     * @return TermFacet[]
     */
    private function createTermFacets()
    {
        $ret = [];

        $ret[] = $this
            ->getSearch()
            ->createTermAggregation('type', null)
            ->setChoicesMap(node_type_get_names())
            ->setTitle($this->t("Type"))
        ;

        $ret[] = $this
            ->getSearch()
            ->createTermAggregation('owner', null)
            ->setChoicesCallback(function ($values) {
                if ($accounts = user_load_multiple($values)) {
                    foreach ($accounts as $index => $account) {
                        $accounts[$index] = filter_xss(format_username($account));
                    }
                    return $accounts;
                }
            })
            ->setTitle($this->t("Owner"))
        ;

        $ret[] = $this
            ->getSearch()
            ->createTermAggregation('tags', null)
            ->setChoicesCallback(function ($values) {
                if ($terms = taxonomy_term_load_multiple($values)) {
                    foreach ($terms as $index => $term) {
                        $terms[$index] = check_plain($term->name);
                    }
                    return $terms;
                }
            })
            ->setTitle($this->t("Tags"))
        ;

        $ret[] = $this
            ->getSearch()
            ->createTermAggregation('status', null)
            ->setChoicesMap([0 => $this->t("Unpublished"), 1 => $this->t("Published")])
            ->setTitle($this->t("Status"))
        ;


        $sites = [];
        foreach ($this->manager->loadOwnSites($this->account) as $site) {
            $sites[$site->getId()] = $site->title;
        }

        $ret[] = $this
            ->getSearch()
            ->createTermAggregation('site_id', null)
            ->setChoicesMap($sites)
            ->setExclusive(true)
            ->setTitle($this->t("My sites"))
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
        foreach ($this->getSearch()->getAggregations() as $facet) {
            $ret[] = (new LinksFilterDisplay($facet->getField(), $facet->getTitle(), true))->setChoicesMap($facet->getFormattedChoices());
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortFields($query)
    {
        return [
            'created'     => $this->t("creation date"),
            'updated'     => $this->t("lastest update date"),
            'status'      => $this->t("status"),
            'owner'       => $this->t("owner"),
            'title'       => $this->t("title"),
            'is_flagged'  => $this->t("flag"),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSort()
    {
        return ['updated', SortManager::DESC];
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
        if ($pageState->hasSortField()) {
            $this->search->addSort($pageState->getSortField(), $pageState->getSortOrder());
        }

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
