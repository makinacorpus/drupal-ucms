<?php

namespace MakinaCorpus\Ucms\Seo\EventDispatcher;

use MakinaCorpus\Ucms\Site\EventDispatcher\SiteAttachEvent;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use MakinaCorpus\Ucms\Seo\SeoService;

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
        $this->service->ensureSitePrimaryAliases($event->getSite()->getId(), $event->getNodeList());
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
            SiteAttachEvent::EVENT_ATTACH => [
                ['onAttach', 0]
            ],
            SiteAttachEvent::EVENT_DETACH => [
                ['onDetach', 0]
            ],
        ];
    }
}
