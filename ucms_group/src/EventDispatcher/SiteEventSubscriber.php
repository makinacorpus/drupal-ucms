<?php

namespace MakinaCorpus\Ucms\Group\EventDispatcher;

use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvents;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Add group functionnality on sites
 */
class SiteEventSubscriber implements EventSubscriberInterface
{
    use GroupContextEventTrait;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            SiteEvents::EVENT_PRECREATE => [
                ['onSitePreCreate', 0]
            ],
        ];
    }

    /**
     * Sets the most relevant 'group_id' property values
     */
    public function onSitePreCreate(SiteEvent $event)
    {
        $site = $event->getSite();

        if (!empty($site->group_id)) {
            return; // Someone took care of this for us
        }

        $site->group_id = $this->findMostRelevantGroupId();
    }
}
