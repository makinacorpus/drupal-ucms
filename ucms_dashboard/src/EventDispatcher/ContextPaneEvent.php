<?php

namespace MakinaCorpus\Ucms\Dashboard\EventDispatcher;

use MakinaCorpus\Ucms\Dashboard\Context\ContextPane;

use Symfony\Component\EventDispatcher\Event;

class ContextPaneEvent extends Event
{
    const EVENT_INIT = 'ucms_dashboard.context_init';

    private $contextPane;

    public function __construct(ContextPane $contextPane)
    {
        $this->contextPane = $contextPane;
    }

    /**
     * @return ContextPane
     */
    public function getContextPane()
    {
        return $this->contextPane;
    }
}
