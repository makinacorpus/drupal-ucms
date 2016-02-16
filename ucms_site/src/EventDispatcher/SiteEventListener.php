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

        if ($site->uid) { // Skips anonymous
            $this
                ->manager
                ->getAccess()
                ->addWebmasters($site, $site->uid)
            ;
        }
    }

    public function onSiteSave(SiteEvent $event)
    {
        // @todo ?
    }
}
