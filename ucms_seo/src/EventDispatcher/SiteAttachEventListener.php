<?php

namespace MakinaCorpus\Ucms\Seo\EventDispatcher;

use MakinaCorpus\Ucms\Seo\SeoService;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteAttachEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvents;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This subscriber will collect linked content within text fields.
 */
class SiteAttachEventListener implements EventSubscriberInterface
{
    private $service;

    public function __construct(SeoService $service)
    {
        $this->service = $service;
    }

    public function onAttach(SiteAttachEvent $event)
    {
        $siteIdList = $event->getSiteIdList();

        if (1 < count($siteIdList)) {
            // @todo optimize me
            foreach ($siteIdList as $siteId) {
                $this->service->ensureSitePrimaryAliases($siteId, $event->getNodeIdList());
            }
        } else {
            $this->service->ensureSitePrimaryAliases(reset($siteIdList), $event->getNodeIdList());
        }
    }

    public function onDetach(SiteAttachEvent $event)
    {
        // @todo me like one of your friench girl
        //   we actually have nothing to do, just leaving the comment for fun
    }

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            SiteEvents::EVENT_ATTACH => [
                ['onAttach', 0]
            ],
            SiteEvents::EVENT_DETACH => [
                ['onDetach', 0]
            ],
        ];
    }
}
