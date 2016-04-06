<?php

namespace MakinaCorpus\Ucms\Seo\EventDispatcher;

use Drupal\Core\Entity\EntityManager;

use MakinaCorpus\Ucms\Seo\SeoService;
use MakinaCorpus\Ucms\Tree\EventDispatcher\MenuEvent;

class MenuEventListener
{
    /**
     * @var SeoService
     */
    private $service;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Default constructor
     *
     * @param SeoService $service
     * @param EntityManager $entityManager
     */
    public function __construct(SeoService $service, EntityManager $entityManager)
    {
        $this->service = $service;
        $this->entityManager = $entityManager;
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
        $deleted = [];
        $changed = [];

        $storage = $this->entityManager->getStorage('node');

        foreach ($event->getDeletedItems() as $item) {
            if ($id = $this->findNodeIdentifierFromItem($item)) {
                $deleted[] = $id;
            }
        }
        if ($deleted) {
            foreach ($storage->loadMultiple($deleted) as $node) {
                $this->service->onAliasRemove($node, $event->getMenuName());
            }
        }

        foreach ($event->getRootItems() as $item) {
            if ($id = $this->findNodeIdentifierFromItem($item)) {
                $changed[] = $id;
            }
        }
        if ($changed) {
            foreach ($storage->loadMultiple($changed) as $node) {
                $this->service->onAliasChange($node, $event->getMenuName());
            }
        }
    }
}
