<?php

namespace MakinaCorpus\Ucms\Tree\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\EventDispatcher\ContextPaneEvent;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Umenu\TreeManager;

class ContextPaneEventListener
{
    use StringTranslationTrait;

    private $siteManager;
    private $treeManager;

    public function __construct(SiteManager $siteManager, TreeManager $treeManager)
    {
        $this->siteManager = $siteManager;
        $this->treeManager = $treeManager;
    }

    /**
     * On context pane init.
     *
     * @param ContextPaneEvent $event
     */
    public function onUcmsdashboardContextinit(ContextPaneEvent $event)
    {
        if (!$this->siteManager->hasContext()) {
            return;
        }

        $contextPane = $event->getContextPane();
        // Add the tree structure as a new tab
        $contextPane
            ->addTab('tree', $this->t("Menu tree"), 'tree-conifer')
            ->add($this->renderCurrentTree(), 'tree')
        ;

        if (!$contextPane->getRealDefaultTab()) {
            $contextPane->setDefaultTab('tree');
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

        foreach ($menus as $menu) {
            $build[$menu['name']] = [
                '#theme'  => 'umenu__context_pane',
                '#tree'   => $this->treeManager->buildTree($menu['id'], false),
                '#prefix' => "<h3>{$menu['title']}</h3>",
            ];
        }

        // Add an edit button
        if ($this->siteManager->getAccess()->userCanEditTree(\Drupal::currentUser(), $site)) {
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
}
