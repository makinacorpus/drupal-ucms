<?php

namespace MakinaCorpus\Ucms\Contrib\Controller;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Contrib\NodeCartDisplay;
use MakinaCorpus\Ucms\Search\SearchFactory;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SearchPaneController extends Controller
{
   use StringTranslationTrait;

    /**
     * @return SearchFactory
     */
    private function getSearchFactory()
    {
        return $this->get('ucms_search.search_factory');
    }

    /**
     * @return SiteManager
     */
    private function getSiteManager()
    {
        return $this->get('ucms_site.manager');
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager()
    {
        return $this->get('entity.manager');
    }

    /**
     * Search action
     */
    public function recentAction(Request $request)
    {
        $search = $this->getSearchFactory()->create('private');
        $search->addSort('updated', 'desc');

        $response = $search
            ->addField('_id')
            ->setLimit(20)
            ->doSearch()
        ;

        $nodeList = $this
            ->getEntityManager()
            ->getStorage('node')
            ->loadMultiple(
                $response->getAllNodeIdentifiers()
            )
        ;

//         $ret = [];

//         if ($nodeList) {
//             $ret['status'] = 1;

//             $display = (new NodeCartDisplay())
//                 ->setParameterName('cd')
//                 ->prepareFromQuery(
//                     $request->query->all()
//                 )
//             ;

//             $renderArray = $display->render($nodeList);
//             foreach (element_children($renderArray) as $key) {
//                 $ret['items'][] = drupal_render($renderArray[$key]);
//             }
//         } else {
//             $ret['status'] = 0;
//         }

//         return new JsonResponse($ret);

        $display = (new NodeCartDisplay())
            ->setParameterName('cd')
            ->prepareFromQuery(
                $request->query->all()
            )
        ;
        return $display->render($nodeList);
    }

    /**
     * Search action
     */
    public function searchAction(Request $request)
    {
        $search = $this->getSearchFactory()->create('private');

        // Create some term facets
//         $facets = [];
//         $facets[] = $search
//             ->createFacet('type')
//             ->setChoicesMap(node_type_get_names())
//             ->setTitle($this->t("Type"))
//         ;

//         if ($pageState->hasSortField()) {
//             $this->search->addSort($pageState->getSortField(), $pageState->getSortOrder());
//         }

        $response = $search
            ->setPageParameter('s')
            ->addField('_id')
            ->setLimit(20)
            ->doSearch($request->query->all())
        ;

        $nodeList = $this
            ->getEntityManager()
            ->getStorage('node')
            ->loadMultiple(
                $response->getAllNodeIdentifiers()
            )
        ;

        $ret = [];

        if ($nodeList) {
            $ret['status'] = 1;

            $display = (new NodeCartDisplay())
                ->setParameterName('cd')
                ->prepareFromQuery(
                    $request->query->all()
                )
            ;

            $renderArray = $display->render($nodeList);
            foreach (element_children($renderArray) as $key) {
                $ret['items'][] = drupal_render($renderArray[$key]);
            }
        } else {
            $ret['status'] = 0;
        }

        return new JsonResponse($ret);
    }
}
