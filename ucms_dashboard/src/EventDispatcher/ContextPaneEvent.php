<?php

namespace MakinaCorpus\Ucms\Dashboard\EventDispatcher;


use MakinaCorpus\Ucms\Dashboard\Context\ContextPane;
use Symfony\Component\EventDispatcher\Event;

class ContextPaneEvent extends Event
{
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
