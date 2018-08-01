<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use MakinaCorpus\Ucms\Site\Site;
use Symfony\Component\HttpFoundation\Request;

/**
 * Site init event
 */
final class SiteInitEvent extends SiteEvent
{
    private $request;

    /**
     * Default constructor
     */
    public function __construct(Site $site, Request $request)
    {
        parent::__construct($site);

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
