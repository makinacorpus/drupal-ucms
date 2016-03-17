<?php

namespace MakinaCorpus\Ucms\Tree\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Umenu\DrupalMenuStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TreeForm extends FormBase
{
    /**
     * @var \MakinaCorpus\Umenu\DrupalMenuStorage
     */
    private $storage;

    /**
     * @var \MakinaCorpus\Ucms\Site\SiteManager
     */
    private $manager;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('umenu.storage'),
            $container->get('ucms_site.manager')
        );
    }

    /**
     * TreeForm constructor.
     *
     * @param \MakinaCorpus\Umenu\DrupalMenuStorage $storage
     * @param \MakinaCorpus\Ucms\Site\SiteManager $manager
     */
    public function __construct(DrupalMenuStorage $storage, SiteManager $manager)
    {
        $this->storage = $storage;
        $this->manager = $manager;
    }


    /**
     * {@inheritDoc}
     */
    public function getFormId()
    {
        return 'ucms_tree_tree_form';
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        // Load all menus for site.
        $site = $this->manager->getContext();
        $menus = $this->storage->loadWithConditions(['site_id' => $site->getId()]);

        $form['#attached']['library'][] = ['ucms_tree', 'nested-sortable'];

        rsort($menus);

        $form['menus']['#tree'] = true;

        foreach ($menus as $menu) {
            $tree = _menu_build_tree($menu['name']);
            // We give all access to nodes, even unpublished
            foreach (array_keys($tree['node_links']) as $nid) {
                foreach ($tree['node_links'][$nid] as $mlid => &$link) {
                    $link['access'] = TRUE;
                }
            }
            // This sorts the menu items
            _menu_tree_check_access($tree['tree']);
            $form['menus'][$menu['name']] = [
                '#type' => 'hidden',
                // Will be filled in Javascript
                // '#value' => '',
            ];
            $form['menus'][$menu['name'].'_list'] = $this->treeOutput($tree['tree'], $menu);
        }
        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type'  => 'submit',
            '#value' => $this->t('Save tree'),
        ];

        return $form;
    }

    /**
     * Save the items in the menus, converting from JS structure to real menu links.
     *
     * @param $menu_name
     * @param $items
     */
    protected function saveMenuItems($menu_name, $items)
    {
        // First, get all elements so that we can delete those that are removed
        $old = array_map(
            function ($link) {
                return $link['mlid'];
            },
            menu_load_links($menu_name)
        );

        // FIXME, this is coming from javascript, we should really check access on nodes

        // Keep a list of processed elements
        $processed = [];

        $weight = -50;
        if (!empty($items)) {
            foreach ($items as $item) {
                $nid = $item['name'];
                $isNew = substr($item['id'], 0, 4) == 'new_';
                $link = [
                    'menu_name'  => $menu_name,
                    'link_path'  => 'node/'.$nid,
                    'link_title' => $this->getNodeTitle($nid),
                    'weight'     => $weight++,
                ];
                if (!$isNew) {
                    $link['mlid'] = $item['id'];
                }
                if ($item['parent_id']) {
                    $link['plid'] = $processed[$item['parent_id']];
                }
                menu_link_save($link);
                $processed[$item['id']] = $link['mlid'];
            }
        }

        // Remove elements not in the original array
        foreach (array_diff($old, $processed) as $deleted) {
            menu_link_delete($deleted);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        foreach ($form_state->getValue('menus') as $menu_name => $items) {
            $this->saveMenuItems($menu_name, drupal_json_decode($items));
        }
    }

    /**
     * Return the title of a node.
     *
     * @param $nid
     * @return mixed
     */
    protected function getNodeTitle($nid)
    {
        // FIXME, not really performant...
        return db_query('SELECT title FROM {node} WHERE nid = :nid', ['nid' => $nid])->fetchField();
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
                $element['data'] = '<div class="tree-item">'.$data['link']['link_title'].'<span class="glyphicon glyphicon-remove"></span></div>';
                $element['data-name'] = substr($data['link']['link_path'], 5); // node/123
                $element['data-mlid'] = $data['link']['mlid'];
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
                $build['#attributes'] = [
                    'data-menu'        => $menu['name'],
                    'data-can-receive' => 1,
                    'class'            => ['sortable'],
                ];
                $build['#title'] = $menu['title'];
                if (empty($tree)) {
                    $build['#items'] = [''];
                }
            }

        }

        return !empty($build['#items']) ? $build : '';
    }
}
