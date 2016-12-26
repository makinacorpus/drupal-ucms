<?php

namespace MakinaCorpus\Ucms\Contrib\Controller;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Contrib\PrivateNodeDataSource;
use MakinaCorpus\Ucms\Dashboard\Controller\PageControllerTrait;

use Symfony\Component\HttpFoundation\Request;

class NodeAdminController extends Controller
{
    use PageControllerTrait;

    /**
     * Get node datasource
     *
     * @return PrivateNodeDataSource
     */
    private function getDatasource()
    {
        return $this->get('ucms_contrib.datasource.elastic');
    }

    /**
     * Main content page.
     *
     * @param string $tab
     *   Tab name.
     */
    private function buildContentPage(Request $request, PrivateNodeDataSource $datasource, $tab = null)
    {
        $search = $datasource->getSearch();

        /** @var \MakinaCorpus\Ucms\Site\SiteManager $siteManager */
        $siteManager = $this->get('ucms_site.manager');

        // Apply context, if any
        if ($siteManager->hasContext()) {
            $search->getFilterQuery()->matchTerm('site_id', $siteManager->getContext()->getId());
        }

        $types = $this->get('ucms_contrib.type_handler')->getTabTypes($tab);
        if (!empty($types)) {
            $search->getFilterQuery()->matchTermCollection('type', $types);
        }

        $builder = $this->getPageBuilder();
        $result = $builder->search($datasource, $request);

        return $builder->render($result);
    }

    /**
     * My content action
     */
    public function mineAction(Request $request, $tab = null)
    {
        /** @var \MakinaCorpus\Ucms\Contrib\PrivateNodeDataSource $datasource */
        $datasource = $this->getDatasource();
        $search = $datasource->getSearch();

        $search
            ->getFilterQuery()
            ->matchTerm('owner', $this->getUser()->id())
        ;

        return $this->buildContentPage($request, $datasource, $tab);
    }

    /**
     * Global content action
     */
    public function globalAction(Request $request, $tab = null)
    {
        $datasource = $this->getDatasource();
        $search = $datasource->getSearch();
        $search
            ->getFilterQuery()
            ->matchTerm('is_global', 1)
            ->matchTerm('is_group', 0)
        ;

        return $this->buildContentPage($request, $datasource, $tab);
    }

    /**
     * Local content action
     */
    public function localAction(Request $request, $tab = null)
    {
        $datasource = $this->getDatasource();
        $search = $datasource->getSearch();
        $search->getFilterQuery()->matchTerm('is_global', 0);

        return $this->buildContentPage($request, $datasource, $tab);
    }

    /**
     * Flagged content action
     */
    public function flaggedAction(Request $request, $tab = null)
    {
        $datasource = $this->getDatasource();
        $search = $datasource->getSearch();
        $search->getFilterQuery()->matchTerm('is_flagged', 1);

        return $this->buildContentPage($request, $datasource, $tab);
    }

    /**
     * Starred content action
     */
    public function starredAction(Request $request, $tab = null)
    {
        $datasource = $this->getDatasource();
        $search = $datasource->getSearch();
        $search->getFilterQuery()->matchTerm('is_starred', 1);

        return $this->buildContentPage($request, $datasource, $tab);
    }
}
