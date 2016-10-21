<?php

namespace MakinaCorpus\Ucms\Dashboard\Controller;

use Drupal\Core\Ajax\AjaxResponse;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Dashboard\Action\ProcessorActionProvider;

use Symfony\Component\HttpFoundation\Request;

class AjaxProcessorController extends Controller
{
    /**
     * @return ProcessorActionProvider
     */
    private function getActionProcessorRegistry()
    {
        return $this->get('ucms_dashboard.processor_registry');
    }

    public function processAction(Request $request)
    {
        if (!$request->query->has('item')) {
            throw $this->createNotFoundException();
        }
        if (!$request->query->has('processor')) {
            throw $this->createNotFoundException();
        }

        try {
            $processor = $this
                ->getActionProcessorRegistry()
                ->get($request->query->get('processor'))
            ;
        } catch (\Exception $e) {
            throw $this->createNotFoundException();
        }

        $item = $processor->loadItem($request->query->get('item'));
        if (!$item) {
            throw $this->createNotFoundException();
        }
        if (!$processor->appliesTo($item)) {
            throw $this->createAccessDeniedException();
        }

        $response = new AjaxResponse();
        $processor->process($item, $response);

        return $response;
    }
}
