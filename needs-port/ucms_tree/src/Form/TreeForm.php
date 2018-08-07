<?php

namespace MakinaCorpus\Ucms\Tree\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Tree\EventDispatcher\MenuEvent;
use MakinaCorpus\Umenu\Menu;
use MakinaCorpus\Umenu\TreeBase;
use MakinaCorpus\Umenu\TreeManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(TreeManager $treeManager, SiteManager $siteManager, \DatabaseConnection $db, EventDispatcherInterface $dispatcher)
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
    public function buildForm(array $form, FormStateInterface $formState, Menu $menu = null)
    {
        if (!$menu) {
            return $form;
        }

        $formState->setTemporaryValue('menu', $menu);

        $form['#attached']['library'][] = ['ucms_tree', 'nested-sortable'];
        $form['#attached']['js'][] = [
          'data' => ['ucmsTree' => ['menuNestingLevel' => variable_get('ucms_tree_menu_nesting_limit', 2)]],
          'type' => 'setting'
        ];

        $form['menu']['#tree'] = true;

        $tree = $this->treeManager->buildTree($menu->getId(), false);

        // This is ugly, but it happens sometime that when menu is empty
        // output goes "", making crashes happen in sf_dic form processing
        // dues to array type hint in form processing functions
        $output = $this->treeOutput($tree, $menu);
        if (!is_array($output)) {
            $output = ['#markup' => $output];
        }
        $form['items'] = $output;
        // Will be filled by JavaScript
        $form['values'] = ['#type' => 'hidden'];

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
    protected function saveMenuItems(Menu $menu, $items)
    {
        $itemStorage  = $this->treeManager->getItemStorage();
        $currentTree  = $this->treeManager->buildTree($menu->getId(), false);
        $menuId       = $menu->getId();

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

            // Because they are ordered and a parent will be saved before a child,
            // thus modifying a child: we have to use a classic loop
            $itemsCount = count($items);
            for ($i = 0; $i < $itemsCount; $i++) {

                $item = $items[$i];
                $nodeId   = $item['name'];
                $isNew    = substr($item['id'], 0, 4) == 'new_' || empty($item['id']);
                $title    = trim($item['title']);
                $itemId   = $isNew ? null : $item['id'];
                $parentId = empty($item['parent_id']) ? null : $item['parent_id'];

                if ($isNew) {
                    if ($parentId) {
                        $id = $itemStorage->insertAsChild($parentId, $nodeId, $title);
                    } else {
                        $id = $itemStorage->insert($menuId, $nodeId, $title);
                    }
                    // New potential parent item inserted, replace potential children parent_id
                    foreach ($items as $index => $potentialChild) {
                        if ($potentialChild['parent_id'] === $item['id']) {
                            $items[$index]['parent_id'] = $id;
                        }
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
        // TODO: if an item is moved from menuA to menuB, it will be deleted in menuA loop, and won't be available in
        // menuB loop, so we need to refactor this to either have a single menu editable per route, or handle deleted
        // items at the end of all menu processing.
        foreach (array_diff_key($old, $processed) as $itemId => $deleted) {
            $itemStorage->delete($itemId);
            $deleteItems[$itemId] = $deleted;
        }

        $this->dispatcher->dispatch(
            MenuEvent::EVENT_TREE,
            new MenuEvent(
                $menu->getName(),
                $newTree,
                $deleteItems,
                $menu->hasSiteId() ? $this->siteManager->getStorage()->findOne($menu->getSiteId()) : null
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function submitForm(array &$form, FormStateInterface $formState)
    {
        try {
            $tx = $this->db->startTransaction();

            $menu = $formState->getTemporaryValue('menu');
            $this->saveMenuItems($menu, json_decode($formState->getValue('values'), true));

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
     * @param Menu $menu
     *
     * @return string
     */
    private function treeOutput(TreeBase $tree, Menu $menu = null)
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

        if ($menu) {
            $build['#attributes'] = [
                'data-menu'        => $menu->getName(),
                'data-can-receive' => 1,
                'class'            => ['sortable'],
            ];
            $build['#title'] = $menu->getTitle();

            // If tree has no children, add an empty element to allow drop.
            if (!$tree->hasChildren()) {
                $build['#items'] = [''];
            }
        }

        return $build;
    }
}
