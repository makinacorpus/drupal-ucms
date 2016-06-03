<?php

namespace MakinaCorpus\Ucms\Tree\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\EventDispatcher\ContextPaneEvent;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Umenu\DrupalMenuStorage;

class ContextPaneEventListener
{
    use StringTranslationTrait;

    /**
     * @var SiteManager
     */
    private $manager;

    /**
     * @var DrupalMenuStorage
     */
    private $storage;

    /**
     * ContextPaneEventListener constructor.
     * @param SiteManager $siteManager
     * @param DrupalMenuStorage $menuStorage
     */
    public function __construct(SiteManager $siteManager, DrupalMenuStorage $menuStorage)
    {
        $this->manager = $siteManager;
        $this->storage = $menuStorage;
    }

    /**
     * On context pane init.
     *
     * @param ContextPaneEvent $event
     */
    public function onUcmsdashboardContextinit(ContextPaneEvent $event)
    {
        $menu_item = menu_get_item();
        if ($menu_item['path'] !== 'node/%' || !$this->manager->hasContext()) {
            return;
        }

        $contextPane = $event->getContextPane();
        // Add the tree structure as a new tab
        $contextPane
            ->addTab('tree', $this->t("Menu tree"), 'tree-conifer')
            ->add($this->render(), 'tree')
        ;

        if (!$contextPane->getRealDefaultTab()) {
            $contextPane->setDefaultTab('tree');
        }
    }

    /**
     *
     */
    private function render()
    {
        $site = $this->manager->getContext();

        // Get all trees for this site
        $menus = $this->storage->loadWithConditions(['site_id' => $site->getId()]);
        rsort($menus);

        $build = [
            '#prefix' => '<div class="col-xs-12">',
            '#suffix' => '</div>',
            '#attached' => [
                'css' => [
                    drupal_get_path('module', 'ucms_tree').'/ucms_tree.css',
                ],
            ],
        ];

        $link = menu_link_get_preferred();

        $parents = [];
        for ($i = 1 ; $i < MENU_MAX_DEPTH ; $i++) {
            if (!empty($link["p$i"])) {
                $parents[] = $link["p$i"];
            }
        }

        foreach ($menus as $menu) {
            $tree_parameters = [];
            $tree_parameters['active_trail'] = $parents;
            $tree = _menu_build_tree($menu['name'], $tree_parameters);
            // This sorts the menu items without access, unlike _menu_tree_check_access().
            $this->sortTree($tree['tree']);
            $build[$menu['name']] = menu_tree_output($tree['tree']);
            $build[$menu['name']]['#prefix'] = "<h3>{$menu['title']}</h3>";
        }

        // Check that this node is referenced in {menu_links}
        // else check for list_type

        // Add an edit button
        if ($this->manager->getAccess()->userCanEditTree(\Drupal::currentUser(), $site)) {
            $build['edit_link'] = [
                '#theme'   => 'link',
                '#path'    => 'admin/dashboard/tree',
                '#text'    => $this->t('Edit tree for this site'),
                '#options' => [
                    'attributes' => ['class' => ['btn btn-primary']],
                    'html' => false,
                ],
            ];
        }

        return $build;
    }

    /**
     * Recursively outputs a tree as nested item lists.
     *
     * @param $tree
     * @param null $menu
     * @return string
     */
    private function treeOutput($tree, $menu = null)
    {
        $items = [];

        if (!empty($tree)) {
            foreach ($tree as $data) {
                $options = [];
                $options['attributes']['class'] = ['tree-item'];

                // FIXME use Request object?
                if (current_path() == $data['link']['link_path']) {
                    $options['attributes']['class'][] = 'active';
                }

                $element = [];
                $element['data'] = l($data['link']['link_title'], $data['link']['link_path'], $options);

                if ($data['below']) {
                    $elements = $this->treeOutput($data['below']);
                    $element['data'] .= drupal_render($elements);
                }

                $items[] = $element;
            }
        }

        $build = [
            '#theme' => 'item_list',
            '#type'  => 'ol',
            '#items' => $items,
        ];

        if ($menu) {
            if ($menu['name']) {
                $build['#title'] = $menu['title'];
                if (empty($tree)) {
                    $build['#items'] = ['<em>'.$this->t("No menu items").'</em>'];
                }
            }
        }

        return !empty($build['#items']) ? $build : '';
    }

    /**
     * Sort a tree
     *
     * @param $tree
     */
    private function sortTree(&$tree)
    {
        $new_tree = [];

        foreach (array_keys($tree) as $key) {

            $item = &$tree[$key]['link'];
            $item['access'] = true;
            $item['menu_name'] = 'admin__'.$item['menu_name'];

            _menu_link_translate($item);

            if ($tree[$key]['below']) {
                $this->sortTree($tree[$key]['below']);
            }

            // The weights are made a uniform 5 digits by adding 50000 as an offset.
            // Adding the mlid to the end of the index insures that it is unique.
            $new_tree[(50000 + $item['weight']) . ' ' . $item['title'] . ' ' . $item['mlid']] = $tree[$key];
        }

        // Sort siblings in the tree based on the weights and localized titles.
        ksort($new_tree);
        $tree = $new_tree;
    }
}
