<?php

namespace MakinaCorpus\Ucms\Contrib\Controller;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Contrib\Page\FlaggedNodePageType;
use MakinaCorpus\Ucms\Contrib\Page\GlobalNodePageType;
use MakinaCorpus\Ucms\Contrib\Page\LocalNodePageType;
use MakinaCorpus\Ucms\Contrib\Page\MyNodePageType;
use MakinaCorpus\Ucms\Contrib\Page\StarredNodePageType;
use MakinaCorpus\Ucms\Dashboard\Controller\PageControllerTrait;
use MakinaCorpus\Ucms\Dashboard\Page\PageBuilder;

use Symfony\Component\HttpFoundation\Request;

class NodeAdminController extends Controller
{
    use PageControllerTrait;

    /**
     * Main content page.
     *
     * @param string $tab
     *   Tab name.
     */
    private function buildContentPage(PageBuilder $builder, Request $request, $tab = null)
    {
        /** @var \MakinaCorpus\Ucms\Site\SiteManager $siteManager */
        $siteManager = $this->get('ucms_site.manager');

        // Apply context, if any
        if ($siteManager->hasContext()) {
            $builder->addBaseQueryParameter('site_id', $siteManager->getContext()->getId());
        }

        $types = $this->get('ucms_contrib.type_handler')->getTabTypes($tab);
        if (!empty($types)) {
            //$search->getFilterQuery()->matchTermCollection('type', $types);
        }

        if ('media' === $tab) {
            $builder->setDefaultDisplay('grid');
        }

        return $builder->searchAndRender($request);
    }

    /**
     * My content action
     */
    public function mineAction(Request $request, $tab = null)
    {
        $request->query->set('user_id', $this->getUser()->id());

        return $this->buildContentPage($this->getPageBuilder(MyNodePageType::class, $request), $request, $tab);
    }

    /**
     * Global content action
     */
    public function globalAction(Request $request, $tab = null)
    {
        return $this->buildContentPage($this->getPageBuilder(GlobalNodePageType::class, $request), $request, $tab);
    }

    /**
     * Local content action
     */
    public function localAction(Request $request, $tab = null)
    {
        return $this->buildContentPage($this->getPageBuilder(LocalNodePageType::class, $request), $request, $tab);
    }

    /**
     * Flagged content action
     */
    public function flaggedAction(Request $request, $tab = null)
    {
        return $this->buildContentPage($this->getPageBuilder(FlaggedNodePageType::class, $request), $request, $tab);
    }

    /**
     * Starred content action
     */
    public function starredAction(Request $request, $tab = null)
    {
        return $this->buildContentPage($this->getPageBuilder(StarredNodePageType::class, $request), $request, $tab);
    }
}
