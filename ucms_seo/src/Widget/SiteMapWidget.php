<?php

namespace MakinaCorpus\Ucms\Seo\Widget;

use Drupal\Core\Entity\EntityInterface;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Widget\WidgetInterface;
use MakinaCorpus\Umenu\TreeManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Displays the site map.
 */
class SiteMapWidget implements WidgetInterface
{
    private $treeManager;

    /**
     * Default constructor
     *
     * @param TreeManager $treeManager
     */
    public function __construct(TreeManager $treeManager)
    {
        $this->treeManager = $treeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function render(EntityInterface $entity, Site $site, $options = [], $formatterOptions = [], Request $request)
    {
        if (!$site) {
            return '';
        }

        $items    = [];
        $menus    = $this->treeManager->getMenuStorage()->loadWithConditions(['site_id' => $site->getId()]);

        /** @var \MakinaCorpus\Umenu\Menu $menu */
        foreach ($menus as $menu) {
            if ($menu->isSiteMain() || !$menu->hasRole()) {
                $tree = $this->treeManager->buildTree($menu->getId(), true);
                if (!$tree->isEmpty()) {
                    $items[$menu->getTitle()] = $tree;
                }
            }
        }

        return ['#theme' => 'ucms_seo_sitemap', '#menus' => $items];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions()
    {
        return ['active' => 1];
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsForm($options = [])
    {
        // Tricks Drupal FAPI in believing there's actually a value
        return [
            'active' => [
                '#type'   => 'value',
                '#value'  => 1,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultFormatterOptions()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatterOptionsForm($options = [])
    {
        return [];
    }
}
