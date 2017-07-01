<?php

namespace MakinaCorpus\Ucms\Contrib\Controller;

use Drupal\node\NodeInterface;
use MakinaCorpus\ACL\Permission;
use MakinaCorpus\Calista\Controller\PageControllerTrait;
use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Contrib\TypeHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class NodeController extends Controller
{
    use PageControllerTrait;

    /**
     * Get view mode for wysiwyg
     *
     * @return mixed
     */
    private function getWysiwygViewMode()
    {
        return $this->getParameter('ucms_contrib.filter.view_mode.wysiwyg');
    }

    /**
     * Get type handler
     *
     * @return TypeHandler
     */
    private function getTypeHandler()
    {
        return $this->get('ucms_contrib.type_handler');
    }

    /**
     * Node admin list page
     */
    public function nodeAdminListAction(Request $request, $tab, $page)
    {
        $pageId = 'ucms_contrib.content_admin.' . $tab;

        return $this->renderPage($pageId, $request, [
            'base_query' => $this->getTypeHandler()->getAdminPageBaseQuery($tab, $page),
        ]);
    }

    /**
     * View node
     */
    public function viewAction(Request $request, NodeInterface $node)
    {
        if (!$this->isGranted(Permission::VIEW, $node)) {
            throw $this->createAccessDeniedException();
        }

        $viewMode = $request->get('mode');
        if (!$viewMode) {
            $viewMode = $this->getWysiwygViewMode();
        }

        // Overwrites the node with its clone if there's one
        // and if the feature is enabled.
        if (variable_get('ucms_contrib_clone_aware_features', false)) {
            $siteManager = $this->get('ucms_site.manager');
            if ($siteManager->hasContext()) {
                $nodeManager = $this->get('ucms_site.node_manager');
                $mapping = $nodeManager->getCloningMapping($siteManager->getContext());

                if (isset($mapping[$node->id()])) {
                    $node = $this->get('entity.manager')
                        ->getStorage('node')
                        ->load($mapping[$node->id()]);
                }
            }
        }

        $view = node_view($node, $viewMode);

        return new JsonResponse(['output' => drupal_render($view)]);
    }
}
