<?php

namespace MakinaCorpus\Ucms\Tree\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Tree\EventDispatcher\MenuEvent;
use MakinaCorpus\Umenu\DrupalMenuStorage;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

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
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('umenu.storage'),
            $container->get('ucms_site.manager'),
            $container->get('database'),
            $container->get('event_dispatcher')
        );
    }

    /**
     * TreeForm constructor.
     *
     * @param \MakinaCorpus\Umenu\DrupalMenuStorage $storage
     * @param \MakinaCorpus\Ucms\Site\SiteManager $manager
     * @param \DatabaseConnection $db
     */
    public function __construct(DrupalMenuStorage $storage, SiteManager $manager, \DatabaseConnection $db, EventDispatcher $dispatcher)
    {
        $this->storage = $storage;
        $this->manager = $manager;
        $this->db = $db;
        $this->dispatcher = $dispatcher;
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
                foreach ($tree['node_links'][$nid] as &$link) {
                    $link['access'] = true;
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
     * @param string $menuName
     * @param mixed[] $items
     */
    protected function saveMenuItems($menuName, $items)
    {
        // First, get all elements so that we can delete those that are removed
        // @todo pri: sorry this is inneficient, but I need it
        $old = [];
        foreach (menu_load_links($menuName) as $item) {
            $old[$item['mlid']] = $item;
        }

        // FIXME, this is coming from javascript, we should really check access on nodes

        // Keep a list of processed elements
        $processed = [];
        $rootItems = [];
        $deleteItems = [];

        $weight = -50;
        if (!empty($items)) {
            foreach ($items as $originalIndex => &$item) {
                $nid = $item['name'];
                $isNew = substr($item['id'], 0, 4) == 'new_';

                $link = [
                    'menu_name'  => $menuName,
                    'link_path'  => 'node/'.$nid,
                    'link_title' => $this->getNodeTitle($nid),
                    'weight'     => $weight++,
                ];

                if (!$isNew) {
                    $link['mlid'] = $item['id'];
                }
                if ($item['parent_id']) {
                    $link['plid'] = $processed[$item['parent_id']]['mlid'];
                }

                $id = menu_link_save($link);
                if ($isNew) {
                    $processed[$item['id']]['mlid'] = $id;
                }
                else {
                    $processed[$id] = $link;
                }

                if (empty($link['plid'])) {
                    $rootItems[$id] = $link;
                }
            }
        }

        // Remove elements not in the original array
        foreach (array_diff_key($old, $processed) as $id => $deleted) {
            menu_link_delete($id);
            $deleteItems[$id] = $deleted;
        }

        $this->dispatcher->dispatch('menu:tree', new MenuEvent($menuName, $rootItems, $deleteItems));
    }

    /**
     * {@inheritDoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        try {
            $tx = $this->db->startTransaction();

            foreach ($form_state->getValue('menus') as $menuName => $items) {
                $this->saveMenuItems($menuName, drupal_json_decode($items));
            }

            unset($tx);

            drupal_set_message($this->t("Tree modifications have been saved"));

        } catch (\Exception $e) {
            if ($tx) {
                try {
                    $tx->rollback();
                } catch (\Exception $e2) {
                    watchdog_exception('ucms_tree', $e2);
                }
                watchdog_exception('ucms_tree', $e);

                drupal_set_message($this->t("Could not save tree modifications"), 'error');
            }
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
            foreach ($tree as $data) {
                $element = [];

                $element['data'] = '<div class="tree-item">'.
                    check_plain($data['link']['link_title']).
                    '<span class="glyphicon glyphicon-remove"></span></div>';
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
