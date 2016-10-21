<?php

namespace MakinaCorpus\Ucms\SmartUI\Controller;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Dashboard\Controller\PageControllerTrait;
use MakinaCorpus\Ucms\Dashboard\Page\TemplateDisplay;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SelectorController extends Controller
{
    use PageControllerTrait;

    /**
     * Selector dialog
     */
    public function searchAction(Request $request)
    {
        // @tood filters from the JS side
        $baseQuery = [];
        $acceptable = $request->getAcceptableContentTypes();

        /** @var \MakinaCorpus\Ucms\Dashboard\Page\PageBuilder $builder */
        $builder = $this->get('ucms_dashboard.page_builder');
        $datasource = $this->get('ucms_smartui.datasource.selector');

        $result = $builder->search($datasource, $request, $baseQuery);

        if (in_array('text/html', $acceptable)) {
            return new Response($builder->render($result, [], 'module:ucms_smartui:views/Selector/search.html.twig'));
        }

        if (in_array('application/json', $acceptable)) {
            $state = $result->getState();

            return new JsonResponse([
                'limit'   => $state->getLimit(),
                'offset'  => $state->getOffset(),
                'page'    => $state->getPageNumber(),
                'items'   => new TemplateDisplay($this->get('tiwg'), 'module:ucms_smartui:Resources/views/Selector/search.html.twig'),
            ]);
        }

        throw $this->createNotFoundException();
    }
}
