<?php

namespace MakinaCorpus\Ucms\Seo\Controller;

use Drupal\node\NodeInterface;
use MakinaCorpus\Calista\Controller\PageControllerTrait;
use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Site\Site;
use Symfony\Component\HttpFoundation\Request;

/**
 * Various SEO admin pages.
 */
class SeoController extends Controller
{
    use PageControllerTrait;

    /**
     * Site alias action
     */
    public function siteAliasListAction(Request $request, Site $site)
    {
        return $this->renderPage('ucms_seo.site_alias', $request, [
            'base_query' => [
                'site' => $site->getId(),
            ],
        ]);
    }

    /**
     * Node redirect list action
     */
    public function nodeRedirectListAction(Request $request, NodeInterface $node)
    {
        return $this->renderPage('ucms_seo.node_redirect', $request, [
            'base_query' => [
                'node' => $node->id(),
            ],
        ]);
    }

    /**
     * Site redirect list action
     */
    public function siteRedirectListAction(Request $request, Site $site)
    {
        return $this->renderPage('ucms_seo.site_redirect', $request, [
            'base_query' => [
                'site' => $site->getId(),
            ],
        ]);
    }
}
