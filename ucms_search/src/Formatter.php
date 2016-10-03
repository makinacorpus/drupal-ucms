<?php

namespace MakinaCorpus\Ucms\Search;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use MakinaCorpus\Ucms\Site\SiteManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Formatter for search display
 */
class Formatter
{
    use StringTranslationTrait;

    /**
     * @var \MakinaCorpus\Ucms\Search\SearchFactory
     */
    private $searchFactory;
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var \MakinaCorpus\Ucms\Site\SiteManager
     */
    private $siteManager;
    private $topHitLimit = 3;
    private $pageLimit = 10;

    public function __construct(
        SearchFactory $searchFactory,
        EntityManager $entityManager,
        SiteManager $siteManager,
        $topHitLimit = 3,
        $pageLimit = 10
    ) {
        $this->searchFactory = $searchFactory;
        $this->entityManager = $entityManager;
        $this->siteManager = $siteManager;
        $this->topHitLimit = $topHitLimit;
        $this->pageLimit = $pageLimit;
    }

    /**
     * @return Search
     */
    private function buildSearch(NodeInterface $node, array $types)
    {
        $search = $this
            ->searchFactory
            ->create('private')
            ->addField('_id')
            ->setFulltextParameterName(Search::PARAM_FULLTEXT_QUERY)
        ;

        // Filter the base query upon the allowed types
        $search->getFilterQuery()->matchTermCollection('type', $types);

        // Only published node
        $search->getFilterQuery()->matchTerm('status', 1);

        // Only nodes for this site
        if ($this->siteManager->hasContext()) {
            $search->getFilterQuery()->matchTerm('site_id', $this->siteManager->getContext()->getId());
        }

        return $search;
    }

    private function buildCommonVariables(Request $request, Response $response)
    {
        $userInput = $request->query->get(Search::PARAM_FULLTEXT_QUERY);

        if ($userInput) {
            $total = $this->formatPlural(
                $response->getTotal(),
                '@count result for "@keywords"',
                '@count results for "@keywords"',
                ['@keywords' => $userInput]
            );
        } else {
            $total = $this->formatPlural(
                $response->getTotal(),
                '@count result',
                '@count results'
            );
        }

        return [
            '#total' => $total,
        ];
    }

    public function render(NodeInterface $node, Request $request, $types)
    {
        $search = $this->buildSearch($node, $types);

        $response = $search
            ->setLimit($this->pageLimit)
            ->doSearch($request->query->all())
        ;

        $nidList = $response->getAllNodeIdentifiers();
        pager_default_initialize($response->getTotal(), $response->getLimit());

        $nodes = $this->entityManager->getStorage('node')->loadMultiple($nidList);

        return $this->buildCommonVariables($request, $response) + [
            'nodes' => node_view_multiple($nodes),
            'pager'   => ['#theme' => 'pager'],
        ];
    }
}
