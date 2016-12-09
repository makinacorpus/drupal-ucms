<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use MakinaCorpus\Ucms\Tree\EventDispatcher\MenuEvent;
use MakinaCorpus\Ucms\Site\NodeManager;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MenuEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var NodeManager
     */
    private $nodeManager;

    /**
     * Default constructor
     *
     * @param NodeManager $siteManager
     * @param EntityManager $entityManager
     */
    public function __construct(NodeManager $nodeManager)
    {
        $this->nodeManager = $nodeManager;
    }

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            MenuEvent::EVENT_TREE => [
                ['onMenuTree', 0]
            ],
        ];
    }

    /**
     * On site context initialization.
     *
     * @param SiteEvent $event
     */
    public function onMenuTree(MenuEvent $event)
    {
        if (!$event->hasSite()) {
            return;
        }

        $deleted = [];
        $changed = [];

        foreach ($event->getDeletedItems() as $item) {
            $deleted[] = $item->getNodeId();
        }
        if ($deleted) {
            // @todo
            //  if node is not present on site, we should clean up the references
            //  except for some types such as news (list by type)
        }

        foreach ($event->getTree()->getAll() as $item) {
            $changed[] = $item->getNodeId();
        }
        if ($changed) {
            $this->nodeManager->createReferenceBulkInSite($event->getSite()->getId(), $changed);
        }
    }
}
