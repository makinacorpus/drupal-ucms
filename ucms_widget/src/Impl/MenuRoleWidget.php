<?php

namespace MakinaCorpus\Ucms\Widget\Impl;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Widget\WidgetInterface;
use MakinaCorpus\Umenu\Menu;
use MakinaCorpus\Umenu\TreeManager;

use Symfony\Component\HttpFoundation\Request;

/**
 * Display a menu where you want it to be.
 *
 * This implementation uses a menu role instead of a menu identifier, which
 * allows the component to be cloned from site to site and always work as
 * expected (ie. displaying the current site menu matching the role).
 *
 * @todo this should be moved into the ucms_tree module, but Drupal 8 does
 *   not allow to conditionnaly load services definitions files since they
 *   don't have symfony's extensions
 */
class MenuRoleWidget implements WidgetInterface
{
    use StringTranslationTrait;

    private $treeManager;
    private $siteManager;

    public function __construct(TreeManager $treeManager, SiteManager $siteManager)
    {
        $this->treeManager = $treeManager;
        $this->siteManager = $siteManager;
    }

    /**
     * Find all the menus with the given role in the given site
     *
     * @param Site $site
     * @param string $role
     *
     * @return Menu[]
     */
    private function findAllMenuWithRole(Site $site, $role)
    {
        return $this->treeManager->getMenuStorage()->loadWithConditions([
            'site_id' => $site->getId(),
            'role'    => $role,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function render(EntityInterface $entity, Site $site, $options = [], $formatterOptions = [], Request $request)
    {
        $ret = [];

        if ($options['role']) {
            try {
                if (!$this->siteManager->hasContext()) {  // Shortcut
                    return;
                }

                $site = $this->siteManager->getContext();

                $menuList = $this->findAllMenuWithRole($site, $options['role']);
                if ($menuList && !$options['multiple']) {
                    $menuList = [reset($menuList)];
                }

                if (!$menuList) { // Shortcut
                    return;
                }

                if ($formatterOptions['suggestion']) {
                    $themeHook = 'umenu__' . $formatterOptions['suggestion'];
                } else {
                    $themeHook = 'umenu';
                }

                $current = null;
                if ($node = menu_get_object()) { // FIXME
                    $current = $node->nid;
                }

                foreach ($menuList as $menu) {
                    $ret[$menu->getName()] = [
                        '#theme'    => $themeHook,
                        '#tree'     => $this->treeManager->buildTree($menu->getName()),
                        '#current'  => $current,
                    ];
                }
            } catch (\Exception $e) {
                // Be silent about this, we are rendering the front page
            }
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions()
    {
        return ['role' => null, 'multiple' => false];
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsForm($options = [])
    {
        $form = [];

        $allowedRoles = ucms_tree_role_list();
        if ($allowedRoles) {
            $form['role'] = [
                '#type'           => 'select',
                '#title'          => $this->t("Menu role"),
                '#options'        => $allowedRoles,
                '#empty_option'   => $this->t("Select a role"),
                '#default_value'  => $options['role'],
                '#required'       => true,
            ];
        } else {
            $form['role'] = [
                '#type'           => 'textfield',
                '#title'          => $this->t("Menu role"),
                '#maxlength'      => 64,
                '#default_value'  => $options['role'],
                '#required'       => true,
            ];
        }

        $form['multiple'] = [
            '#type'           => 'checkbox',
            '#title'          => $this->t("Display all matching menus"),
            '#description'    => $this->t("If unchecked, only the first menu found with the given role in the current site will be displayed"),
            '#default_value'  => $options['multiple'],
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultFormatterOptions()
    {
        return [
            'suggestion' => 'node',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatterOptionsForm($options = [])
    {
        $form = [];

        $form['suggestion'] = [
            '#type'           => 'textfield',
            '#title'          => $this->t("Template suggestion suffix"),
            '#default_value'  => $options['suggestion'],
            '#required'       => false,
        ];

        return $form;
    }
}
