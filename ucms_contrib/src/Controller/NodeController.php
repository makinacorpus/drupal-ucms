<?php

namespace MakinaCorpus\Ucms\Contrib\Controller;

use Drupal\node\NodeInterface;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Dashboard\Controller\PageControllerTrait;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class NodeController extends Controller
{
    use PageControllerTrait;

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

    /**
     * Node selector
     */
    public function selectorAction(Request $request)
    {
        // Node selector providers a clean window to select any kind of content
        // and security here is not much at risk since it will be driven by node
        // so don't care about checking any access rightsfrom here.

        // @todo
        //   - filter by content type
        //   - default selection

        return $this
            ->createTemplatePage(
                $this->get('ucms_contrib.datasource.selector'),
                'module:ucms_contrib:views/Node/selector.html.twig'
            )
            ->setBaseQuery([])
            ->render($request->query->all())
        ;
    }
}
