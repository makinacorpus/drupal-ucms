<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

/**
 * Master admin site (no context) is initializing
 */
final class MasterInitEvent extends Event
{
    /**
     * @var Request
     */
    private $request;

    /**
     * Default constructor
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Get request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }
}
