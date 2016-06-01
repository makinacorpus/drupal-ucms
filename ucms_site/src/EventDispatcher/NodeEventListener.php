<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use Drupal\Core\Entity\EntityManager;

use MakinaCorpus\APubSub\Notification\EventDispatcher\ResourceEvent;
use MakinaCorpus\Ucms\Site\SiteManager;

class NodeEventListener
{
    /**
     * @var SiteManager
     */
    private $siteManager;
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(SiteManager $siteManager, EntityManager $entityManager)
    {
        $this->siteManager = $siteManager;
        $this->entityManager = $entityManager;
    }

    public function onNodeAccessChange(ResourceEvent $event)
    {
        // Rebuild node access rights
        $nodes = $this->entityManager->getStorage('node')->loadMultiple($event->getResourceIdList());
        foreach ($nodes as $node) {
            node_access_acquire_grants($node);
        }
    }
}
