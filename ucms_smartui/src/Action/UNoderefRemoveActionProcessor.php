<?php

namespace MakinaCorpus\Ucms\SmartUI\Action;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RemoveCommand;

use MakinaCorpus\Ucms\Dashboard\SmartObject;

class UNoderefRemoveActionProcessor extends AbstractAjaxProcessor
{
    /**
     * {@inheritDoc}
     */
    public function appliesTo($item)
    {
        return $item instanceof SmartObject && $item->getContext() === SmartObject::CONTEXT_UNODEREF;
    }

    /**
     * {@inheritDoc}
     */
    public function process($item, AjaxResponse $response)
    {
        $response->addCommand(new RemoveCommand('[data-nid='.$this->getItemId($item).']'));
    }
}
