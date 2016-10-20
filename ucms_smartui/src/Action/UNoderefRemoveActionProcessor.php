<?php

namespace MakinaCorpus\Ucms\SmartUI\Action;

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
    public function process($item)
    {
        return [
            // TODO handle context here
            ajax_command_remove('[data-nid='.$this->getItemId($item).']'),
        ];
    }
}
