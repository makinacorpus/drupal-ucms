<?php

namespace MakinaCorpus\Ucms\Seo\Controller;

use Drupal\Core\Entity\EntityManager;
use Drupal\node\NodeInterface;
use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Dashboard\Controller\PageControllerTrait;
use MakinaCorpus\Ucms\Seo\SeoService;
use MakinaCorpus\Ucms\Seo\Page\RedirectDisplay;
use MakinaCorpus\Ucms\Seo\Page\SiteAliasDisplay;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;

class SeoController extends Controller
{
    use PageControllerTrait;

    private function getSiteManager(): SiteManager
    {
        return $this->get('ucms_site.manager');
    }

    private function getSeoService(): SeoService
    {
        return $this->get('ucms_seo.service');
    }

    private function getEntityManager(): EntityManager
    {
        return $this->get('entity.manager');
    }

    public function siteAliasListAction(Site $site)
    {
        $datasource = \Drupal::service('ucms_seo.admin.site_alias_datasource');
        $display = new SiteAliasDisplay($this->getSiteManager(), $this->getEntityManager(), t("This site has no SEO alias."));

        $query = ['site' => $site->getId()];

        return $this
            ->createPage($datasource, $display, ['dashboard', 'seo', 'aliases'])
            ->setBaseQuery($query)
            ->render(\drupal_get_query_parameters(), \current_path())
        ;
    }

    public function nodeRedirectListAction(NodeInterface $node)
    {
        $datasource = \Drupal::service('ucms_seo.admin.redirect_datasource');
        $siteManager = $this->getSiteManager();
        $display = new RedirectDisplay($siteManager, $this->getEntityManager(), t("This content has no SEO redirect."));

        $query = ['node' => $node->id()];
        if ($siteManager->hasContext()) {
            // $query['site'] = $siteManager->getContext()->getId();
        }

        return $this
            ->createPage($datasource, $display, ['dashboard', 'seo', 'redirect'])
            ->setBaseQuery($query)
            ->render(\drupal_get_query_parameters(), \current_path())
        ;
    }

    public function siteRedirectListAction(Site $site)
    {
        $siteManager = $this->getSiteManager();
        $datasource = \Drupal::service('ucms_seo.admin.redirect_datasource');
        $display = new RedirectDisplay($siteManager, $this->getEntityManager(), $site, t("This site has no SEO redirect."));

        $query = ['site' => $site->getId()];

        return $this
            ->createPage($datasource, $display, ['dashboard', 'seo', 'redirect'])
            ->setBaseQuery($query)
            ->render(\drupal_get_query_parameters(), \current_path())
        ;
    }
}
