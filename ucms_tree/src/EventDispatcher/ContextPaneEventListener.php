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
            ->setDefaultTab('tree')
        ;
    }

    /**
     *
     */
    private function render()
    {
        // Get all trees for this site
        $menus = $this->storage->loadWithConditions(['site_id' => $this->manager->getContext()->getId()]);
        rsort($menus);

        $build = [
            '#attached' => [
                'css' => [
                    drupal_get_path('module', 'ucms_tree').'/ucms_tree.css',
                ],
            ],
        ];
        foreach ($menus as $menu) {
            $tree = _menu_build_tree($menu['name']);
            // We give all access to nodes, even unpublished
            foreach (array_keys($tree['node_links']) as $nid) {
                foreach ($tree['node_links'][$nid] as $mlid => &$link) {
                    if (!$link['access']) {
                        $link['access'] = true;
                        $link['not_published'] = true;
                    }
                }
            }
            // This sorts the menu items
            _menu_tree_check_access($tree['tree']);
            $build[$menu['name']] = $this->treeOutput($tree['tree'], $menu);
        }

        // Check that this node is referenced in {menu_links}
        // else check for list_type
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
            foreach ($tree as $i => $data) {
                $classes = ['tree-item'];
                // FIXME use Request object?
                if (current_path() == $data['link']['link_path']) {
                    $classes[] = 'active';
                }
                $element['data'] = '<div class="'.implode(' ', $classes).'">';
                $element['data'] .= check_plain($data['link']['link_title']).'</div>';
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
}
