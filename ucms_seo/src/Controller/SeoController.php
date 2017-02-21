<?php

namespace MakinaCorpus\Ucms\Seo\Controller;

use Drupal\node\NodeInterface;
use MakinaCorpus\Drupal\Dashboard\Controller\PageControllerTrait;
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
        return $this
            ->createPageBuilder()
            ->setDatasource(\Drupal::service('ucms_seo.admin.site_alias_datasource'))
            ->setAllowedTemplates(['table' => 'module:ucms_seo:Page/page-site-aliases.html.twig'])
            ->setBaseQuery(['site' => $site->getId()])
            ->searchAndRender($request)
        ;
    }

    /**
     * Node redirect list action
     */
    public function nodeRedirectListAction(Request $request, NodeInterface $node)
    {
        return $this
            ->createPageBuilder()
            ->setDatasource(\Drupal::service('ucms_seo.admin.node_redirect_datasource'))
            ->setAllowedTemplates(['table' => 'module:ucms_seo:Page/page-node-redirect.html.twig'])
            ->setBaseQuery(['node' => $node->id()])
            ->searchAndRender($request)
        ;
    }

    /**
     * Site redirect list action
     */
    public function siteRedirectListAction(Request $request, Site $site)
    {
        return $this
            ->createPageBuilder()
            ->setDatasource(\Drupal::service('ucms_seo.admin.site_redirect_datasource'))
            ->setAllowedTemplates(['table' => 'module:ucms_seo:Page/page-site-redirect.html.twig'])
            ->setBaseQuery(['site' => $site->getId()])
            ->searchAndRender($request)
        ;
    }
}
