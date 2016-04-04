<?php

namespace MakinaCorpus\Ucms\Seo\Controller;

use MakinaCorpus\Drupal\Sf\Controller;

use Symfony\Component\HttpFoundation\Response;

class SitemapController extends Controller
{

    public function displayAction($display='html')
    {
        $site = $this->get('ucms_site.manager')->getContext();
        $menus = $this->get('umenu.storage')->loadWithConditions(['site_id' => $site->getId()]);

        if ($display === 'xml') {
            return $this->displayXML($menus);
        }

        return $this->displayHTML($menus);
    }

    private function displayXML($menus)
    {
        $menus_tree = [];
        foreach ($menus as $menu_name => $menu) {
            $menus_tree[] = menu_build_tree($menu_name);
        }

        $output = $this->renderView(
            'module:ucms_seo:views/sitemap.xml.twig',
            ['menus_tree' => $menus_tree]
        );

        return new Response(
            $output,
            200,
            array('content-type' => 'application/xml')
        );
    }

    private function displayHTML($menus)
    {
        $build = [];

        foreach ($menus as $menu_name => $menu) {
            $tree = menu_build_tree($menu_name);
            $menu_output = menu_tree_output($tree);
            if ($menu_output) {
                $build[$menu_name] = $menu_output;
            }
        }

        return theme('ucms_seo_sitemap', [
            'menus' => $build,
        ]);
    }
}
