<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

/**
 * Master admin site (no context) is initializing
 */
class MasterInitEvent extends Event
{
    /**
     * @var Request
     */
    private $request;

    /**
     * Default constructor
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Get request
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}
