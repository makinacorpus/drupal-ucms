<?php

namespace MakinaCorpus\Ucms\Layout\Action;

use Drupal\Core\Ajax\AjaxResponse;

use MakinaCorpus\Ucms\Dashboard\SmartObject;
use MakinaCorpus\Ucms\SmartUI\Action\AbstractAjaxProcessor;

class LayoutViewModeProcessor extends AbstractAjaxProcessor
{

    /**
     * {@inheritDoc}
     */
    public function appliesTo($item)
    {
        return $item instanceof SmartObject && $item->getContext() === SmartObject::CONTEXT_LAYOUT;
    }

    /**
     * {@inheritDoc}
     */
    public function process($item, AjaxResponse $response)
    {
        // TODO: replace html of element
    }


    // TODO override url

    // TODO get position
}
