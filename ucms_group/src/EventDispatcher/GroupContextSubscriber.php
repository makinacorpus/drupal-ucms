<?php

namespace MakinaCorpus\Ucms\Group\EventDispatcher;

use MakinaCorpus\Ucms\Group\GroupManager;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvents;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteInitEvent;
use MakinaCorpus\Ucms\Site\SiteManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * All context alterations events into the same subscriber, because it does
 * not mean anything to disable one or the other, it's all or nothing.
 */
class GroupContextSubscriber implements EventSubscriberInterface
{
    /**
     * @var SiteManager
     */
    private $siteManager;

    /**
     * @var GroupManager
     */
    private $groupManager;

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        // Priority here ensures it happens after the 'ucms_site' node event
        // subscriber, and that we will have all site information set
        return [
            SiteEvents::EVENT_INIT => [
                ['onSiteInit', 0],
            ],
        ];
    }

    /**
     * Default constructor
     *
     * @param SiteManager $siteManager
     * @param GroupManager $groupManager
     */
    public function __construct(SiteManager $siteManager, GroupManager $groupManager)
    {
        $this->siteManager = $siteManager;
        $this->groupManager = $groupManager;
    }

    /**
     * Set current group context
     */
    public function onSiteInit(SiteInitEvent $event)
    {
        $group = $this->groupManager->getAccess()->getSiteGroup($event->getSite());

        if ($group) {
            $this->siteManager->setDependentContext('group', $group);
        }
    }
}
