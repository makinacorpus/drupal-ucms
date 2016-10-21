<?php

namespace MakinaCorpus\Ucms\SmartUI\Action;

use Drupal\Core\Ajax\AjaxResponse;

use MakinaCorpus\Ucms\Dashboard\SmartObject;
use MakinaCorpus\Ucms\SmartUI\Ajax\NewPageCommand;

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
    public function process($item, AjaxResponse $response)
    {
        $response->addCommand(new NewPageCommand('node/'.$item->getNode()->id()));
    }
}
