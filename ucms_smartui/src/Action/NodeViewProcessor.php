<?php

namespace MakinaCorpus\Ucms\SmartUI\Action;

use MakinaCorpus\Ucms\Dashboard\SmartObject;

class NodeViewProcessor extends AbstractAjaxProcessor
{
    /**
     * {@inheritDoc}
     */
    public function appliesTo($item)
    {
        return $item instanceof SmartObject;
    }

    /**
     * {@inheritDoc}
     */
    public function process($item)
    {
        return [
            ucms_smartui_command_new_page('node/'.$item->getNode()->id()),
        ];
    }
}
