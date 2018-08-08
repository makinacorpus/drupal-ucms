<?php

namespace MakinaCorpus\Ucms\Tree\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use MakinaCorpus\Calista\Datasource\DatasourceInputDefinition;
use MakinaCorpus\Calista\Datasource\DatasourceInterface;
use MakinaCorpus\Calista\Query\Query;
use MakinaCorpus\Calista\Query\QueryFactory;
use MakinaCorpus\Calista\Twig\View\TwigView;
use MakinaCorpus\Calista\View\ViewDefinition;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Umenu\TreeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use MakinaCorpus\Umenu\Menu;

class AdminController extends ControllerBase
{
    private $allowedMenus;
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
        \Twig_Environment $twig,
        TreeManager $treeManager,
        DatasourceInterface $menuDatasource,
        array $allowedMenus = []
    ) {
        $this->allowedMenus = $allowedMenus;
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
     * Menu tree display and edit links
     */
    public function menuTree(Request $request, Menu $menu)
    {
        throw new \Exception("not implemented yet");
    }
}
