<?php

namespace MakinaCorpus\Ucms\Contrib\Controller;

use Drupal\node\NodeInterface;

use MakinaCorpus\Drupal\Sf\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class NodeController extends Controller
{
    private function getWysiwygViewMode()
    {
        return $this->getParameter('ucms_contrib.filter.view_mode.wysiwyg');
    }

    public function viewAction(NodeInterface $node, $mode = 'normal')
    {
        $view = node_view($node, $this->getWysiwygViewMode());

        return new JsonResponse(['output' => drupal_render($view)]);
    }
}
