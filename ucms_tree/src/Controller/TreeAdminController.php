<?php

namespace MakinaCorpus\Ucms\Tree\Controller;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Session\AccountInterface;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Contrib\ContentTypeManager;
use MakinaCorpus\Ucms\Dashboard\AdminWidgetFactory;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Tree\Form\TreeEditForm;
use MakinaCorpus\Ucms\Tree\Form\TreeForm;
use MakinaCorpus\Ucms\Tree\MenuAccess;
use MakinaCorpus\Ucms\Tree\Page\TreeAdminDatasource;
use MakinaCorpus\Ucms\Tree\Page\TreeAdminDisplay;
use MakinaCorpus\Umenu\Menu;

use Symfony\Component\HttpFoundation\Request;

class TreeAdminController extends Controller
{
    /**
     * @return ContentTypeManager
     */
    private function getTypeManager()
    {
        return $this->get('ucms_contrib.type_manager');
    }

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
     * @return MenuAccess
     */
    private function getMenuAccess()
    {
        return $this->get('ucms_tree.menu_access');
    }

    /**
     * @return SiteManager
     */
    private function getSiteManager()
    {
        return $this->get('ucms_site.manager');
    }

    /**
     * @return AccountInterface
     */
    private function getCurrentUser()
    {
        return $this->get('current_user');
    }

    /**
     * @return FormBuilderInterface
     */
    private function getDrupalFormBuilder()
    {
        return $this->get('form_builder');
    }

    /**
     * @return AdminWidgetFactory
     */
    private function getAdminWidgetFactory()
    {
        return $this->get('ucms_dashboard.admin_widget_factory');
    }

    /**
     * Overview menu access
     */
    public function accessMenuOverview()
    {
        return $this->getMenuAccess()->canAccessMenuAdmin($this->getCurrentUser());
    }

    /**
     * Add menu access
     */
    public function accessMenuAdd()
    {
        return $this->getMenuAccess()->canCreateMenu($this->getCurrentUser());
    }

    /**
     * Add new menu in site action
     */
    public function menuAddAction()
    {
        return $this->getDrupalFormBuilder()->getForm(TreeEditForm::class);
    }

    /**
     * Edit menu access
     */
    public function accessMenuEdit(Menu $menu)
    {
        return $this->getMenuAccess()->canEditMenu($menu, $this->getCurrentUser());
    }

    /**
     * Edit menu action
     */
    public function menuEditAction(Menu $menu)
    {
        return $this->getDrupalFormBuilder()->getForm(TreeEditForm::class, $menu);
    }

    /**
     * Edit menu items access
     */
    public function accessMenuTreeEdit(Menu $menu)
    {
        return $this->getMenuAccess()->canEditTree($menu, $this->getCurrentUser());
    }

    /**
     * Edit menu items action
     */
    public function menuTreeAction(Menu $menu)
    {
        return $this->getDrupalFormBuilder()->getForm(TreeForm::class, $menu);
    }

    /**
     * Administrative tree list
     */
    public function treeListAction(Request $request)
    {
        if (!$this->getMenuAccess()->canAccessMenuAdmin($this->getCurrentUser())) {
            throw $this->createAccessDeniedException();
        }

        $datasource = $this->getTreeAdminDatasource();
        $display    = $this->getTreeAdminDisplay();
        $query      = [];

        $siteManager = $this->getSiteManager();
        if ($siteManager->hasContext()) {
            $query['site'] = $siteManager->getContext()->getId();
        }

        $page = $this->getAdminWidgetFactory()->getPage($datasource, $display, ['tree-admin']);
        $page->setBaseQuery($query);

        return $page->render($request->query->all(), current_path()); //@todo current_path()
    }

    /**
     * Provides minidialog for creating content at a specific position
     *
     * @param Request $request
     * @return array
     */
    public function addContentHere(Request $request)
    {
        $links = [];
        $handler = $this->getTypeManager();

        foreach ($handler->getTypeNames($handler->getNonMediaTypes()) as $type => $name) {
            if (node_access('create', $type)) {
                $options = [
                    'query' => [
                        'destination' => $request->get('destination'),
                        'menu_name'   => $request->get('menu'),
                        'parent'      => $request->get('parent'),
                        'position'    => $request->get('position'),
                    ],
                ];
                $links[] = l($name, 'node/add/'.strtr($type, '_', '-'), $options);
            }
        }

        return [
            '#theme' => 'item_list',
            '#items' => $links,
        ];
    }
}
