<?php

namespace MakinaCorpus\Ucms\Contrib\Controller;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Dashboard\Controller\PageControllerTrait;

use Symfony\Component\HttpFoundation\Request;

class NodeAdminController extends Controller
{
    use PageControllerTrait;

    /**
     * Render a node admin page
     */
    public function defaultAction(Request $request, $name)
    {
        // We don't need to check permissions twice, Drupal already did it
        return $this->getPageBuilder($name, $request)->searchAndRender($request);
    }
}
