<?php

namespace MakinaCorpus\Ucms\Seo\Controller;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Umenu\Menu;
use MakinaCorpus\Umenu\TreeManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * Displays site maps.
 */
class SitemapController extends Controller
{
    /**
     * @return TreeManager
     */
    private function getTreeManager()
    {
        return $this->get('umenu.manager');
    }

    /**
     * Display site map action
     */
    public function displayAction($display = 'html')
    {
        $site   = $this->get('ucms_site.manager')->getContext();
        $menus  = $this->get('umenu.menu_storage')->loadWithConditions(['site_id' => $site->getId()]);

        if ('xml' === $display) {
            return $this->displayXML($menus);
        }

        return $this->displayHTML($menus);
    }

    /**
     * Display site map as XML
     *
     * @param Menu[]
     *
     * @return string
     */
    private function displayXML($menus)
    {
        $treeList = [];
        $manager  = $this->getTreeManager();

        /** @var \MakinaCorpus\Umenu\Menu $menu */
        foreach ($menus as $menu) {
            $tree = $manager->buildTree($menu->getId(), true);
            if (!$tree->isEmpty()) {
                $treeList[] = $tree;
            }
        }

        $output = $this->renderView('module:ucms_seo:views/sitemap.xml.twig', ['menus_tree' => $treeList]);

        return new Response($output, 200, ['content-type' => 'application/xml']);
    }

    /**
     * Display site map as HTML
     *
     * @param Menu[]
     *
     * @return string
     */
    private function displayHTML($menus)
    {
        $build    = [];
        $manager  = $this->getTreeManager();

        /** @var \MakinaCorpus\Umenu\Menu $menu */
        foreach ($menus as $menu) {
            if ($menu->isSiteMain() || !$menu->hasRole()) {
                $tree = $manager->buildTree($menu->getId(), true);
                if (!$tree->isEmpty()) {
                    $build[$menu->getTitle()] = $tree;
                }
            }
        }

        return theme('ucms_seo_sitemap', ['menus' => $build]);
    }
}
