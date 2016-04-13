<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use MakinaCorpus\Ucms\Tree\EventDispatcher\MenuEvent;
use MakinaCorpus\Ucms\Site\NodeDispatcher;

class MenuEventListener
{
    /**
     * @var NodeDispatcher
     */
    private $nodeDispatcher;

    /**
     * Default constructor
     *
     * @param NodeDispatcher $siteManager
     * @param EntityManager $entityManager
     */
    public function __construct(NodeDispatcher $nodeDispatcher)
    {
        $this->nodeDispatcher = $nodeDispatcher;
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
            $this->nodeDispatcher->createReferenceBulk($event->getSite(), $changed);
        }
    }
}
