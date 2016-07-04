<?php

namespace MakinaCorpus\Ucms\Tree\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Tree\EventDispatcher\MenuEvent;
use MakinaCorpus\Umenu\TreeBase;
use MakinaCorpus\Umenu\TreeManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class TreeForm extends FormBase
{
    private $treeManager;
    private $siteManager;
    private $db;
    private $dispatcher;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('umenu.manager'),
            $container->get('ucms_site.manager'),
            $container->get('database'),
            $container->get('event_dispatcher')
        );
    }

    /**
     * TreeForm constructor.
     *
     * @param TreeManager $treeManager
     * @param SiteManager $siteManager
     * @param \DatabaseConnection $db
     */
    public function __construct(TreeManager $treeManager, SiteManager $siteManager, \DatabaseConnection $db, EventDispatcher $dispatcher)
    {
        $this->treeManager = $treeManager;
        $this->siteManager = $siteManager;
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
        $site = $this->siteManager->getContext();
        $form_state->setTemporaryValue('site', $site);

        $menus = $this->treeManager->getMenuStorage()->loadWithConditions(['site_id' => $site->getId()]);

        $form['#attached']['library'][] = ['ucms_tree', 'nested-sortable'];

        rsort($menus);

        $form['menus']['#tree'] = true;

        foreach ($menus as $menu) {
            $tree = $this->treeManager->buildTree($menu['id'], false);

            $form['menus'][$menu['name']] = [
                '#type' => 'hidden',
                // '#value' => '', // Will be filled in Javascript
            ];
            $form['menus'][$menu['name'].'_list'] = $this->treeOutput($tree, $menu);
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
     * @param string $menuId
     * @param mixed[] $items
     * @param Site $site
     */
    protected function saveMenuItems($menuName, $items, Site $site = null)
    {
        $itemStorage  = $this->treeManager->getItemStorage();
        $currentTree  = $this->treeManager->buildTree($menuName, false);
        $menu         = $this->treeManager->getMenuStorage()->load($menuName);
        $menuId       = $menu['id'];

        // First, get all elements so that we can delete those that are removed
        // @todo pri: sorry this is inneficient, but I need it
        $old = [];
        foreach ($currentTree->getAll() as $item) {
            $old[$item->getId()] = $item;
        }

        // FIXME, this is coming from javascript, we should really check access on nodes

        // Keep a list of processed elements
        $processed = [];
        $deleteItems = [];

        // Keep in mind that items ordered
        if (!empty($items)) {
            foreach ($items as $item) {

                $nodeId   = $item['name'];
                $isNew    = substr($item['id'], 0, 4) == 'new_' || empty($item['id']);
                $title    = trim($item['title']);
                $itemId   = $isNew ? null : $item['id'];
                $parentId = empty($item['parent_id']) ? null : $item['parent_id'];

                if ($isNew) {
                    if ($parentId) {
                        $item = $itemStorage->insertAsChild($parentId, $nodeId, $title);
                    } else {
                        $item = $itemStorage->insert($menuId, $nodeId, $title);
                    }
                } else {
                    if ($parentId) {
                        $itemStorage->moveAsChild($itemId, $parentId);
                    } else {
                        $itemStorage->moveToRoot($itemId);
                    }

                    // Update title if revelant
                    if ($title !== $currentTree->getItemById($itemId)->getTitle()) {
                        $itemStorage->update($itemId, null, $title);
                    }
                }

                $processed[$itemId] = true;
            }
        }

        $newTree = $this->treeManager->buildTree($menuId, false, false, true);

        // Remove elements not in the original array
        foreach (array_diff_key($old, $processed) as $itemId => $deleted) {
            $itemStorage->delete($itemId);
            $deleteItems[$itemId] = $deleted;
        }

        $this->dispatcher->dispatch('menu:tree', new MenuEvent($menuName, $newTree, $deleteItems, $site));
    }

    /**
     * {@inheritDoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        try {
            $tx = $this->db->startTransaction();

            $site = $form_state->getTemporaryValue('site');

            foreach ($form_state->getValue('menus') as $menuName => $items) {
                $this->saveMenuItems($menuName, drupal_json_decode($items), $site);
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
     * Recursively outputs a tree as nested item lists.
     *
     * @param TreeBase $tree
     * @param string[] $menu
     *
     * @return string
     */
    private function treeOutput(TreeBase $tree, $menu = null)
    {
        $items = [];

        foreach ($tree->getChildren() as $item) {
            $element = [];

            $input = [
                '#prefix'         => '<div class="tree-item clearfix">',
                '#type'           => 'textfield',
                '#attributes'     => ['class' => ['']],
                '#value'          => $item->getTitle(),
                '#theme_wrappers' => [],
                '#suffix'         => '<span class="glyphicon glyphicon-remove"></span></div>',
            ];
            $element['data'] = drupal_render($input);
            $element['data-name'] = $item->getNodeId();
            $element['data-mlid'] = $item->getId();

            if ($item->hasChildren()) {
                $elements = $this->treeOutput($item);
                $element['data'] .= drupal_render($elements);
            }

            $items[] = $element;
        }

        $build = [
            '#theme' => 'item_list',
            '#type'  => 'ol',
            '#items' => $items,
        ];

        if ($menu && isset($menu['name']) && isset($menu['title'])) {
            $build['#attributes'] = [
                'data-menu'        => $menu['name'],
                'data-can-receive' => 1,
                'class'            => ['sortable'],
            ];
            $build['#title'] = $menu['title'];

            // If tree has no children, add an empty element to allow drop.
            if (!$tree->hasChildren()) {
                $build['#items'] = [''];
            }
        }

        return $build;
    }
}
