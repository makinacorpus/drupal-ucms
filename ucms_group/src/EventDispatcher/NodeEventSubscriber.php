<?php

namespace MakinaCorpus\Ucms\Group\EventDispatcher;

use MakinaCorpus\Ucms\Contrib\EventDispatcher\NodeEvent;
use MakinaCorpus\Ucms\Group\GroupManager;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NodeEventSubscriber implements EventSubscriberInterface
{
    private $siteManager;
    private $groupManager;

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        // Priority here ensures it happens after the 'ucms_site' node event
        // subscriber, and that we will have all site information set
        return [
            NodeEvent::EVENT_PREPARE => [
                ['onPrepare', 10]
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

    public function onPrepare(NodeEvent $event)
    {
        $node = $event->getNode();

        if ($node->isNew() && $this->siteManager->hasContext()) {
            // Set the is_ghost property according to site it's being inserted in
            // @todo
        }
    }
}
