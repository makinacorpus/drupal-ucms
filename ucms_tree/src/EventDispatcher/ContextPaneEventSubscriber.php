<?php

namespace MakinaCorpus\Ucms\Tree\EventDispatcher;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Drupal\Dashboard\EventDispatcher\ContextPaneEvent;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Tree\MenuAccess;
use MakinaCorpus\Umenu\TreeManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use MakinaCorpus\Drupal\Dashboard\Action\Action;

class ContextPaneEventSubscriber implements EventSubscriberInterface
{
    use StringTranslationTrait;

    private $currentUser;
    private $menuAccess;
    private $siteManager;
    private $treeManager;

    /**
     * Default constructor
     *
     * @param SiteManager $siteManager
     * @param TreeManager $treeManager
     */
    public function __construct(SiteManager $siteManager, TreeManager $treeManager, MenuAccess $menuAcess, AccountInterface $currentUser)
    {
        $this->siteManager = $siteManager;
        $this->treeManager = $treeManager;
        $this->menuAccess = $menuAcess;
        $this->currentUser = $currentUser;
    }

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            ContextPaneEvent::EVENT_INIT => [
                ['onUcmsdashboardContextinit', 0],
            ],
        ];
    }

    /**
     * On context pane init.
     *
     * @param ContextPaneEvent $event
     */
    public function onUcmsdashboardContextinit(ContextPaneEvent $event)
    {
        $site = null;
        $contextPane = $event->getContextPane();

        if ($this->siteManager->hasContext()) {
            $site = $this->siteManager->getContext();
        }

        // Add admin actions on the tree listing page
        if ('admin/dashboard/tree' === current_path()) {
            $canCreate = false;
            if ($site) {
                $canCreate = $this->menuAccess->canCreateMenu($this->currentUser, $site);
            } else {
                $canCreate = $this->menuAccess->canCreateMenu($this->currentUser);
            }
            if ($canCreate) {
                $contextPane->addActions([
                    new Action($this->t("Create menu"), 'admin/dashboard/tree/add', [], 'plus', 0, true, true),
                ]);
            }
        }

        if ($site) {
            // Add the tree structure as a new tab
            $contextPane
                ->addTab('tree', $this->t("Menu tree"), 'tree-conifer')
                ->add($this->renderCurrentTree(), 'tree')
            ;

            if (preg_match('@^admin/dashboard/tree/(\d+)$@', current_path())) {
                // Default tab on tree edit is the cart
                $contextPane->setDefaultTab('cart');
            } elseif (!$contextPane->getRealDefaultTab()) {
                // Else it's the tree
                $contextPane->setDefaultTab('tree');
            }
        }
    }

    /**
     * Render current tree
     */
    private function renderCurrentTree()
    {
        $site = $this->siteManager->getContext();

        // Get all trees for this site
        $menus = $this
            ->treeManager
            ->getMenuStorage()
            ->loadWithConditions(['site_id' => $site->getId()])
        ;

        $build = [
            '#prefix' => '<div class="col-xs-12">',
            '#suffix' => '</div>',
            '#attached' => [
                'css' => [
                    drupal_get_path('module', 'ucms_tree').'/ucms_tree.css',
                ],
            ],
        ];

        foreach ($menus as $menu) {
            $build[$menu->getName()] = [
                '#theme'  => 'umenu__context_pane',
                '#tree'   => $this->treeManager->buildTree($menu->getId(), false),
                '#prefix' => "<h3>" . $menu->getTitle() . "</h3>",
            ];
        }

        return $build;
    }
}
