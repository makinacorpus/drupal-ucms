<?php

namespace MakinaCorpus\Ucms\Tree\Controller;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Dashboard\Page\PageFactory;
use MakinaCorpus\Ucms\Tree\Page\TreeAdminDatasource;
use MakinaCorpus\Ucms\Tree\Page\TreeAdminDisplay;

use Symfony\Component\HttpFoundation\Request;

class TreeAdminController extends Controller
{
    /**
     * @return TreeAdminDatasource
     */
    private function getTreeAdminDatasource()
    {
        return $this->get('ucms_tree.admin.datasource');
    }

    /**
     * @return TreeAdminDisplay
     */
    private function getTreeAdminDisplay()
    {
        return $this->get('ucms_tree.admin.display');
    }

    /**
     * @return PageFactory
     */
    private function getPageFactory()
    {
        return $this->get('ucms_dashboard.page_factory');
    }

    /**
     * Administrative tree list
     */
    public function treeListAction(Request $request)
    {
        $datasource = $this->getTreeAdminDatasource();
        $display    = $this->getTreeAdminDisplay();

        $page       = $this->getPageFactory()->get($datasource, $display, ['tree-admin']);

        return $page->render($request->query->all(), current_path()); //@todo current_path()
    }
}
