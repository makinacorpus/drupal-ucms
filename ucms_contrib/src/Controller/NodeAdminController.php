<?php

namespace MakinaCorpus\Ucms\Contrib\Controller;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Dashboard\Controller\PageControllerTrait;
use MakinaCorpus\Ucms\Dashboard\Page\PageBuilder;

use Symfony\Component\HttpFoundation\Request;

class NodeAdminController extends Controller
{
    use PageControllerTrait;

    /**
     * Get service name for page type
     *
     * @param string $tab
     *   'content' or 'media' or anything that the type handler knows about
     * @param string $pool
     *   'mine', 'global', etc...
     *
     * @return string
     */
    static public function getServiceName($tab, $pool)
    {
        return 'ucms_contrib.page_type.' . $tab . '.' . $pool;
    }

    /**
     * Get additional filters for pool
     *
     * @param string $pool
     *   'mine', 'global', etc...
     *
     * @return mixed[]
     */
    static public function getQueryFilter($pool)
    {
        switch ($pool) {

            case 'mine':
                return []; // Dynamic

            case 'global':
                return [
                    'is_global' => 1,
                    'is_group' => 0,
                ];

            case 'local':
                return [
                    'is_global' => 0,
                ];

            case 'flagged':
                return [
                    'is_flagged' => 1,
                ];

            case 'starred':
                return [
                    'is_starred' => 1,
                ];
        }

        return [];
    }

    /**
     * Main content page.
     *
     * @param string $tab
     *   Tab name.
     */
    private function buildContentPage(PageBuilder $builder, Request $request, $tab = null)
    {
        return $builder->searchAndRender($request);
    }

    /**
     * My content action
     */
    public function mineAction(Request $request, $tab = null)
    {
        $request->query->set('user_id', $this->getUser()->id());

        return $this->getPageBuilder($this->getServiceName($tab, 'mine'), $request)->searchAndRender($request);
    }

    /**
     * Default render action
     */
    public function defaultAction(Request $request, $pool = 'local', $tab = null)
    {
        return $this->getPageBuilder($this->getServiceName($tab, $pool), $request)->searchAndRender($request);
    }
}
