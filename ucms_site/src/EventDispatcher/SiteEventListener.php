<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use MakinaCorpus\Ucms\Site\SiteManager;

class SiteEventListener
{
    /**
     * @var SiteManager
     */
    private $manager;

    /**
     * Default constructor
     *
     * @param SiteManager $manager
     */
    public function __construct(SiteManager $manager)
    {
        $this->manager = $manager;
    }

    public function onSiteCreate(SiteEvent $event)
    {
        $site = $event->getSite();

        $this
            ->manager
            ->getAccess()
            ->addWebmasters($site, $site->uid)
        ;
    }

    public function onSiteUpdate(SiteEvent $event)
    {
        // @todo ?
    }
}
