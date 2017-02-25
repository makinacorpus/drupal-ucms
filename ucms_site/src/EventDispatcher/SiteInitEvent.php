<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use MakinaCorpus\Ucms\Site\Site;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

/**
 * Site init event
 */
class SiteInitEvent extends Event
{
    /**
     * @var Site
     */
    private $site;

    /**
     * @var Request
     */
    private $request;

    /**
     * Default constructor
     *
     * @param Site $site
     * @param Request $request
     */
    public function __construct(Site $site, Request $request)
    {
        $this->site = $site;
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

    /**
     * Get site
     *
     * @return Site
     */
    public function getSite()
    {
        return $this->site;
    }
}
