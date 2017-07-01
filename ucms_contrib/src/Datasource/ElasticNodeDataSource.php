<?php

namespace MakinaCorpus\Ucms\Contrib\Datasource;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use MakinaCorpus\Calista\Datasource\AbstractDatasource;
use MakinaCorpus\Calista\Datasource\DefaultDatasourceResult;
use MakinaCorpus\Calista\Datasource\Filter;
use MakinaCorpus\Calista\Datasource\Query;
use MakinaCorpus\Ucms\Search\Aggs\TermFacet;
use MakinaCorpus\Ucms\Search\NodeIndexerInterface;
use MakinaCorpus\Ucms\Search\Search;
use MakinaCorpus\Ucms\Search\SearchFactory;
use MakinaCorpus\Ucms\Site\SiteManager;

class ElasticNodeDataSource extends AbstractDatasource
{
    use StringTranslationTrait;

    /**
     * @var Search
     */
    private $search;

    /**
     * @var SiteManager
     */
    private $manager;

    /**
     * @var EntityManager
     */
    private $entityManager;

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
    public function __construct(SearchFactory $searchFactory, SiteManager $manager, EntityManager $entityManager, AccountInterface $account, $index = 'private')
    {
        $this->search = $searchFactory->create($index);
        $this->manager = $manager;
        $this->entityManager = $entityManager;
        $this->account = $account;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemClass()
    {
        return NodeIndexerInterface::class;
    }

    /**
     * Get datasource search object
     *
     * @return Search
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
            ->createFacet('type', null)
            ->setChoicesMap(node_type_get_names())
            ->setTitle($this->t("Type"))
        ;

        $ret[] = $this
            ->getSearch()
            ->createFacet('owner', null)
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
            ->createFacet('tags', null)
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
            ->createFacet('status', null)
            ->setChoicesMap([0 => $this->t("Unpublished"), 1 => $this->t("Published")])
            ->setExclusive(true)
            ->setTitle($this->t("Status"))
        ;

        if (!$this->manager->hasContext()) {
            $sites = [];
            foreach ($this->manager->loadOwnSites($this->account) as $site) {
                $sites[$site->getId()] = check_plain($site->title);
            }

            $ret[] = $this
                ->getSearch()
                ->createFacet('site_id', null)
                ->setChoicesMap($sites)
                ->setExclusive(true)
                ->setTitle($this->t("My sites"))
            ;
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        $ret = [];

        // Apply rendering stuff for it to work
        foreach ($this->getSearch()->getAggregations() as $facet) {
            $ret[] = (new Filter($facet->getField(), $facet->getTitle(), true))->setChoicesMap($facet->getFormattedChoices());
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function getSorts()
    {
        return [
            'created'     => $this->t("creation date"),
            'updated'     => $this->t("lastest update date"),
            'status'      => $this->t("status"),
            'owner'       => $this->t("owner"),
            'title.raw'   => $this->t("title"),
            'is_flagged'  => $this->t("flag"),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSort()
    {
        return ['updated', Query::SORT_DESC];
    }

    /**
     * {@inheritdoc}
     */
    private function init(Query $query)
    {
        $filters = $query->all();

        if ($filters) {
            $filterQuery = $this->search->getFilterQuery();

            foreach ($filters as $name => $value) {
                if (is_array($value)) {
                    $filterQuery->matchTermCollection($name, $value);
                } else {
                    $filterQuery->matchTerm($name, $value);
                }
            }
        }

        $this->createTermFacets();
    }

    /**
     * Preload pretty much everything to make admin listing faster
     *
     * @param NodeInterface[]
     */
    private function preloadDependencies(array $nodeList)
    {
        $userIdList = [];
        $siteIdList = [];

        foreach ($nodeList as $node) {
            $userIdList[$node->uid] = $node->uid;
            foreach ($node->ucms_sites as $siteId) {
                $siteIdList[$siteId] = $siteId;
            }
        }

        if ($userIdList) {
            $this->entityManager->getStorage('user')->loadMultiple($userIdList);
        }
        if ($siteIdList) {
            $this->manager->getStorage()->loadAll($siteIdList);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(Query $query)
    {
        if ($query->hasSortField()) {
            $this->search->addSort($query->getSortField(), $query->getSortOrder());
        }

        $inputDefinition = $query->getInputDefinition();

        $response = $this
            ->search
            ->setPageParameter($inputDefinition->getPagerParameter())
            ->setFulltextParameterName($inputDefinition->getSearchParameter())
            ->addField('_id')
            ->setLimit($query->getLimit())
            ->doSearch($query->getRouteParameters()) // FIXME this should be the sanitized filters + a few others (sort, etc...)
        ;

        $nodeList = $this->entityManager->getStorage('node')->loadMultiple($response->getAllNodeIdentifiers());
        $this->preloadDependencies($nodeList);

        return $this->createResult($nodeList, $response->getTotal());
    }

    /**
     * {@inheritdoc}
     */
    public function supportsFulltextSearch()
    {
        return true;
    }
}
