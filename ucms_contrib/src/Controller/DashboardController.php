<?php

namespace MakinaCorpus\Ucms\Contrib\Controller;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Dashboard\Controller\PageControllerTrait;
use MakinaCorpus\Ucms\Dashboard\Page\DatasourceInterface;
use Symfony\Component\HttpFoundation\Request;

class DashboardController extends Controller
{
    use PageControllerTrait;

    private function getReferenceDatasource() : DatasourceInterface
    {
        return $this->get('ucms_contrib.datasource.reference');
    }

    /**
     * View all groups action
     */
    public function viewAllReference(Request $request)
    {
        return $this
            ->createTemplatePage(
                $this->getReferenceDatasource(),
                '@ucms_contrib/views/page/reference-admin.html.twig'
            )
            ->render($request->query->all())
        ;
    }
}
