<?php

namespace MakinaCorpus\Ucms\Dashboard\Controller;

use Drupal\Core\Form\FormBuilderInterface;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Dashboard\Action\ProcessorActionProvider;
use MakinaCorpus\Ucms\Dashboard\Form\ActionProcessForm;

use Symfony\Component\HttpFoundation\Request;

class ActionProcessorController extends Controller
{
    /**
     * @return FormBuilderInterface
     */
    private function getFormBuilder()
    {
        return $this->get('form_builder');
    }

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

        $builder = $this->getFormBuilder();

        return $builder->getForm($processor->getFormClass(), $processor, $item);
    }
}
