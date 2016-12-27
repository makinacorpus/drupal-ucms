<?php

namespace MakinaCorpus\Ucms\Dashboard\Controller;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Dashboard\Page\DatasourceInterface;

use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxPageController extends Controller
{
    use PageControllerTrait;

    /**
     * Create datasource from request
     *
     * @param Request $request
     *
     * @return DatasourceInterface
     */
    private function getDatasourceFromRequest(Request $request)
    {
        $datasourceId = $request->get('datasource');
        $datasource = null;

        if (!$datasourceId) {
            throw $this->createNotFoundException();
        }

        try {
            $datasource = $this->get($datasource);
        } catch (ServiceNotFoundException $e) {
            throw $this->createNotFoundException();
        }

        if (!$datasource instanceof DatasourceInterface) {
            throw $this->createNotFoundException();
        }

        return $datasource;
    }

    /**
     * Type search action
     */
    public function searchAction(Request $request)
    {
        $uuid         = $request->get('uuid');
        $datasource   = $this->getDatasourceFromRequest($request);
        $pageBuilder  = $this->getPageBuilder($name);
        $result       = $pageBuilder->search($datasource, $request);
        $page         = $pageBuilder->render($result, []);

        return new JsonResponse([
            'filters'       => $page->renderPartial('filters'),
            'item_list'     => $page->renderPartial('item_list'),
            'pager'         => $page->renderPartial('pager'),
        ]);
    }

    /**
     * Refresh everything
     */
    public function refreshAction(Request $request)
    {
        $name         = $request->get('name');
        $datasource   = $this->getDatasourceFromRequest($request);
        $pageBuilder  = $this->getPageBuilder($name);
        $result       = $pageBuilder->search($datasource, $request);
        $page         = $pageBuilder->render($result, []);

        return new JsonResponse([
            'filters'       => $page->renderPartial('filters'),
            'display_mode'  => $page->renderPartial('display_mode'),
            'sort_links'    => $page->renderPartial('sort_links'),
            'item_list'     => $page->renderPartial('item_list'),
            'pager'         => $page->renderPartial('pager'),
        ]);
    }
}
