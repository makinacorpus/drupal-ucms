<?php

namespace MakinaCorpus\Ucms\Tree\Controller;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Session\AccountInterface;
use MakinaCorpus\Calista\Controller\PageControllerTrait;
use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Contrib\TypeHandler;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Tree\Form\TreeEditForm;
use MakinaCorpus\Ucms\Tree\Form\TreeForm;
use MakinaCorpus\Ucms\Tree\MenuAccess;
use MakinaCorpus\Umenu\Menu;
use Symfony\Component\HttpFoundation\Request;

class TreeAdminController extends Controller
{
    use PageControllerTrait;

    /**
     * @return TypeHandler
     */
    private function getTypeHandler()
    {
        return $this->get('ucms_contrib.type_handler');
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

        return $this->renderPage('ucms_tree.list_all', $request);
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
        $handler = $this->getTypeHandler();
        foreach ($this->getTypeHandler()
                      ->getTypesAsHumanReadableList($handler->getContentTypes()) as $type => $name) {
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
