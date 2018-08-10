<?php

namespace MakinaCorpus\Ucms\Tree\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\RouteMatchInterface;
use MakinaCorpus\Calista\Datasource\DatasourceInputDefinition;
use MakinaCorpus\Calista\Datasource\DatasourceInterface;
use MakinaCorpus\Calista\Query\Query;
use MakinaCorpus\Calista\Query\QueryFactory;
use MakinaCorpus\Calista\Twig\View\TwigView;
use MakinaCorpus\Calista\View\ViewDefinition;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Umenu\Menu;
use MakinaCorpus\Umenu\TreeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AdminController extends ControllerBase
{
    private $allowedMenus;
    private $database;
    private $eventDispatcher;
    private $menuDatasource;
    private $treeManager;
    private $twig;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('event_dispatcher'),
            $container->get('database'),
            $container->get('twig'),
            $container->get('umenu.manager'),
            $container->get('ucms_tree.admin.datasource'),
            $container->getParameter('ucms_tree_allowed_menus')
        );
    }

    /**
     * Compute menu identifier
     *
     * @todo mutualize this with SiteEventListener
     */
    private function getMenuName(Site $site, string $prefix = 'main'): string
    {
        return $prefix.'-'.$site->getId();
    }

    /**
     * Create missing menus for site and return the created menu list
     *
     * @todo mutualize this with SiteEventListener
     */
    private function ensureSiteMenus(Site $site): array
    {
        $ret = [];
        if ($this->allowedMenus) {
            $storage = $this->treeManager->getMenuStorage();
            foreach ($this->allowedMenus as $prefix => $title) {
                $name = $this->getMenuName($site, $prefix);
                if (!$storage->exists($name)) {
                    $ret[$name] = $storage->create($name, ['title' => $this->t($title), 'site_id' => $site->getId()]);
                }
            }
        }
        return $ret;
    }

    /**
     * Default constructor
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        Connection $database,
        \Twig_Environment $twig,
        TreeManager $treeManager,
        DatasourceInterface $menuDatasource,
        array $allowedMenus = []
    ) {
        $this->allowedMenus = $allowedMenus;
        $this->database = $database;
        $this->eventDispatcher = $eventDispatcher;
        $this->menuDatasource = $menuDatasource;
        $this->treeManager = $treeManager;
        $this->twig = $twig;
    }

    /**
     * Administrative tree list
     */
    public function menuList(Request $request, Site $site)
    {
        $this->ensureSiteMenus($site);

        $inputDefinition = new DatasourceInputDefinition($this->menuDatasource, [
            'base_query' => [
                'site_id' => $site->getId(),
            ],
            'search_enable' => false,
            'search_parse' => false,
            'sort_default_field' => 'm.title',
            'sort_default_order' => Query::SORT_ASC,
        ]);
        $viewDefinition = new ViewDefinition([
            'templates' => [
                'default' => '@ucms_tree/admin/menu-list.html.twig',
            ],
        ]);

        $query = (new QueryFactory())->fromRequest($inputDefinition, $request);
        $items = $this->menuDatasource->getItems($query);

        $view = new TwigView($this->twig, $this->eventDispatcher);

        return [
            // Drupal 8 will filter for XSS if you provide a raw string here
            // hence the need of encapsulating it into Markup::create().
            '#markup' => Markup::create($view->render($viewDefinition, $items, $query)),
        ];
    }

    /**
     * Flatten and validate incomming tree
     */
    private function validateIncommingTree(Menu $menu, array $tree, $parentId = null): array
    {
        $ret = [];

        $previousId = null;
        foreach ($tree as $item) {

            if (empty($item['id']) && empty($item['title'])) {
                throw new BadRequestHttpException();
            }
            $currentItemId = (int)$item['id'];

            // Generated tree is a set of commands to pass to the item tree
            // storage in the given order: if 'after' is set, use the "move
            // item after" command, else if 'parent' is set, use "insert as
            // child" command, if none, then "move to root" instead.
            // Since that ALL items are being processed, if any is misplaced
            // temporarily during the transaction, it will be replaced
            // correctly when its own siblings will be moved themselves.
            $ret[] = [
                'id' => $currentItemId,
                'title' => $item['title'],
                'parent' => $parentId,
                'after' => $previousId
            ];

            $previousId = $currentItemId;

            // Flatten children and dispose them after the current item.
            if (isset($item['children'])) {
                if (!\is_array($item['children'])) {
                    throw new BadRequestHttpException();
                }
                foreach ($this->validateIncommingTree($menu, $item['children'], $currentItemId) as $child) {
                    $ret[] = $child;
                }
            }
        }

        return $ret;
    }

    /**
     * From incomming request, find and save tree.
     */
    private function saveTreeFromRequest(Request $request, Menu $menu)
    {
        $content = $request->getContent();
        $input = null;

        if (!\is_string($content) || (!$input = @\json_decode($content, true)) || !isset($input['tree'])) {
            throw new BadRequestHttpException();
        }

        $flattenedTree = $this->validateIncommingTree($menu, $input['tree']);
        $itemStorage = $this->treeManager->getItemStorage();

        try {
            $tx = $this->database->startTransaction();

            $existing = [];
            foreach ($flattenedTree as $item) {
                $existing[] = $itemId = $item['id'];
                if ($item['after']) {
                    $itemStorage->moveAfter($itemId, $item['after']);
                } else if ($item['parent']) {
                    $itemStorage->moveAsChild($itemId, $item['parent']);
                } else {
                    $itemStorage->moveToRoot($itemId);
                }
                // @todo update title and description
            }

            // @todo provide an helper for this in umenu
            if ($existing) {
                $this->database->query("DELETE FROM {umenu_item} WHERE menu_id = :id AND id NOT IN (:list[])", [':id' => $menu->getId(), ':list[]' => $existing]);
            }

            unset($tx); // Explicit commit.
            // \drupal_set_message($this->t("Tree saved"));

        } catch (\Throwable $e) {
            if ($tx) {
                try {
                    $tx->rollback();
                } catch (\Exception $e2) {
                    \watchdog_exception('ucms_tree', $e2);
                }
                \watchdog_exception('ucms_tree', $e);
                // \drupal_set_message($this->t("Could not save tree"), 'error');
            }
        }
    }

    /**
     * Menu tree display and edit links
     */
    public function menuTree(Request $request, RouteMatchInterface $routeMatch, Menu $menu)
    {
        if ($request->isMethod('post') || $request->isMethod('put')) {
            $this->saveTreeFromRequest($request, $menu);

            return $this->redirect($routeMatch->getRouteName(), $routeMatch->getParameters()->all());
        }

        return [
            '#markup' => Markup::create('<div class="ucms-menu-tree" data-menu-tree-edit="'.$menu->getId().'"></div>'),
            '#attached' => ['library' => ['ucms_tree/tree']],
        ];
    }

    /**
     * Recursively create a data structure suitable for the front side.
     */
    private function recursiveTreeToJson(array $items): array
    {
        $ret = [];
        $expansionThreshold = 4;
        /** @var \MakinaCorpus\Umenu\TreeItem $item */
        foreach ($items as $item) {
            $ret[] = [
                'id' => $item->getId(),
                'title' => $item->getTitle(),
                'expanded' => $expansionThreshold < $item->getChildCount(),
                'children' => $this->recursiveTreeToJson($item->getChildren())
            ];
        }
        return $ret;
    }

    /**
     * Fetch the tree using AJAX
     */
    public function ajaxMenuTree(Request $request, RouteMatchInterface $routeMatch, Menu $menu)
    {
        if ($request->isMethod('post') || $request->isMethod('put')) {
            $this->saveTreeFromRequest($request, $menu);
        }

        $tree = $this->treeManager->buildTree($menu->getId(), false, null, true);

        return new JsonResponse(['tree' => $this->recursiveTreeToJson($tree->getChildren())]);
    }
}
