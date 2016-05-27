<?php

namespace MakinaCorpus\Ucms\Contrib\Controller;

use Drupal\node\NodeInterface;

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
        if (!$node->access('view')) {
            throw $this->createAccessDeniedException();
        }

        $viewMode = $request->get('mode');
        if (!$viewMode) {
            $viewMode = $this->getWysiwygViewMode();
        }

        $view = node_view($node, $viewMode);

        return new JsonResponse(['output' => drupal_render($view)]);
    }
}
