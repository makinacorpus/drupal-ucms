<?php

namespace MakinaCorpus\Ucms\Contrib\Controller;

use Drupal\node\NodeInterface;

use MakinaCorpus\ACL\Permission;
use MakinaCorpus\Drupal\Sf\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class NodeController extends Controller
{
    private function getWysiwygViewMode()
    {
        return $this->getParameter('ucms_contrib.filter.view_mode.wysiwyg');
    }

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
