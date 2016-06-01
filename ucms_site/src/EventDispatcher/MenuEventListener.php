<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use MakinaCorpus\Ucms\Tree\EventDispatcher\MenuEvent;
use MakinaCorpus\Ucms\Site\NodeManager;

class MenuEventListener
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

    private function findNodeIdentifierFromItem($item)
    {
        $matches = [];
        if (preg_match('@^node/(\d+)$@', $item['link_path'], $matches)) {
            return (int)$matches[1];
        }
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
            if ($id = $this->findNodeIdentifierFromItem($item)) {
                $deleted[] = $id;
            }
        }
        if ($deleted) {
            // @todo
            //  if node is not present on site, we should clean up the references
            //  except for some types such as news (list by type)
        }

        foreach ($event->getAllItems() as $item) {
            if ($id = $this->findNodeIdentifierFromItem($item)) {
                $changed[] = $id;
            }
        }
        if ($changed) {
            $this->nodeManager->createReferenceBulkInSite($event->getSite()->getId(), $changed);
        }
    }
}
