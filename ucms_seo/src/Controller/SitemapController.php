<?php

namespace MakinaCorpus\Ucms\Seo\Controller;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Umenu\TreeManager;

use Symfony\Component\HttpFoundation\Response;

class SitemapController extends Controller
{
    /**
     * @return TreeManager
     */
    private function getTreeManager()
    {
        return $this->get('umenu.manager');
    }

    public function displayAction($display = 'html')
    {
        $site   = $this->get('ucms_site.manager')->getContext();
        $menus  = $this->get('umenu.menu_storage')->loadWithConditions(['site_id' => $site->getId()]);

        if ('xml' === $display) {
            return $this->displayXML($menus);
        }

        return $this->displayHTML($menus);
    }

    private function displayXML($menus)
    {
        $treeList = [];
        $manager  = $this->getTreeManager();

        foreach (array_keys($menus) as $menuName) {
            $tree = $manager->buildTree($menuName, true);
            if (!$tree->isEmpty()) {
                $treeList[$menuName] = $tree;
            }
        }

        $output = $this->renderView('module:ucms_seo:views/sitemap.xml.twig', ['menus_tree' => $treeList]);

        return new Response($output, 200, ['content-type' => 'application/xml']);
    }

    private function displayHTML($menus)
    {
        $build    = [];
        $manager  = $this->getTreeManager();

        foreach (array_keys($menus) as $menuName) {
            $tree = $manager->buildTree($menuName, true);
            if (!$tree->isEmpty()) {
                $build[$menuName] = $tree;
            }
        }

        return theme('ucms_seo_sitemap', ['menus' => $build]);
    }
}
