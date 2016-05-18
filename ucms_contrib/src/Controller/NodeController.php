<?php

namespace MakinaCorpus\Ucms\Contrib\Controller;

use Drupal\node\NodeInterface;

use MakinaCorpus\Drupal\Sf\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class NodeController extends Controller
{
    public function viewAction(NodeInterface $node, $mode = 'normal')
    {
        $view = node_view($node, 'default');

        return new JsonResponse(['output' => drupal_render($view)]);
    }
}
