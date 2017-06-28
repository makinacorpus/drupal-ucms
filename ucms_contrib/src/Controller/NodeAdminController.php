<?php

namespace MakinaCorpus\Ucms\Contrib\Controller;

use MakinaCorpus\Calista\Controller\PageControllerTrait;
use MakinaCorpus\Drupal\Sf\Controller;
use Symfony\Component\HttpFoundation\Request;

class NodeAdminController extends Controller
{
    use PageControllerTrait;

    /**
     * Render a node admin page
     */
    public function defaultAction(Request $request, $name)
    {
        return $this->renderPageResponse($name, $request);
    }
}
