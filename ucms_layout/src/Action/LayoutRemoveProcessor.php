<?php

namespace MakinaCorpus\Ucms\Layout\Action;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\SmartObject;
use MakinaCorpus\Ucms\Layout\ContextManager;
use MakinaCorpus\Ucms\SmartUI\Action\AbstractAjaxProcessor;

class LayoutRemoveProcessor extends AbstractAjaxProcessor
{
    use StringTranslationTrait;

    /**
     * @var \MakinaCorpus\Ucms\Layout\ContextManager
     */
    private $contextManager;

    public function __construct(ContextManager $contextManager)
    {
        $this->contextManager = $contextManager;

        parent::__construct($this->t('Remove from cart'), 'trash', 10);
    }

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

    }

    // TODO override url

    // TODO get position
}
