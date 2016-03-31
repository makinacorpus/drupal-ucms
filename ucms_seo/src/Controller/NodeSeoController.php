<?php

namespace MakinaCorpus\Ucms\Seo\Controller;

use Drupal\node\NodeInterface;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Dashboard\Page\PageFactory;
use MakinaCorpus\Ucms\Seo\Page\NodeAliasDisplay;
use MakinaCorpus\Ucms\Seo\SeoService;
use MakinaCorpus\Ucms\Site\SiteManager;

class NodeSeoController extends Controller
{
    /**
     * @return SiteManager
     */
    private function getSiteManager()
    {
        return $this->get('ucms_site.manager');
    }

    /**
     * @return SeoService
     */
    private function getSeoService()
    {
        return $this->get('ucms_seo.service');
    }

    /**
     * @return PageFactory
     */
    private function getPageFactory()
    {
        return $this->get('ucms_dashboard.page_factory');
    }

    public function aliasesListAction(NodeInterface $node)
    {
        $datasource = \Drupal::service('ucms_seo.admin.node_alias_datasource');
        $display    = new NodeAliasDisplay($this->getSiteManager(), t("This content has no SEO alias."));

        $query  = ['node' => $node->id()];

        return $this
            ->getPageFactory()
            ->get($datasource, $display, ['dashboard', 'seo', 'aliases'])
            ->setBaseQuery($query)
            ->render(drupal_get_query_parameters(), current_path())
        ;
    }
}
