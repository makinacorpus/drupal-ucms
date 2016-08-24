<?php

namespace MakinaCorpus\Ucms\Layout\EventDispatcher;

use Drupal\Core\Entity\EntityManager;

use MakinaCorpus\Ucms\Layout\ContextManager;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteAttachEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvents;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Subscribes to node within sites specific events in order to provide
 * additional layout handling logic.
 */
final class SiteNodeEventSubscriber implements EventSubscriberInterface
{
    private $db;
    private $contextManager;
    private $siteManager;
    private $entityManager;
    private $requestStack;

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            SiteEvents::EVENT_ATTACH => [
                ['onAttach', -64] // Must happen after the reference has been done
            ],
        ];
    }

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param ContextManager $contextManager
     * @param SiteManager $siteManager
     * @param RequestStack $requestStack
     */
    public function __construct(
        \DatabaseConnection $db,
        ContextManager $contextManager,
        SiteManager $siteManager,
        EntityManager $entityManager,
        RequestStack $requestStack
    ) {
        $this->db = $db;
        $this->contextManager = $contextManager;
        $this->siteManager = $siteManager;
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    /**
     * When referencing a node on site, we clone its original layout the same
     * time so the user does not get an empty page.
     */
    public function onAttach(SiteAttachEvent $event)
    {
        $siteIdList = $event->getSiteIdList();
        /* @var \Drupal\node\NodeInterface[] $nodeList */
        $nodeList = $this->entityManager->getStorage('node')->loadMultiple($event->getNodeIdList());

        $pageContext = $this->contextManager->getPageContext();
        $storage = $pageContext->getStorage();

        // @todo Find a better way
        foreach ($siteIdList as $siteId) {
            foreach ($nodeList as $node) {

                if (!$node->site_id) {
                    continue;
                }

                // Ensure a layout does not already exists (for example when
                // cloning a node, the layout daaa already has been inserted
                // if the original was existing)
                $exists = (bool)$this
                    ->db
                    ->query(
                        "SELECT 1 FROM {ucms_layout} WHERE nid = :nid AND site_id = :sid",
                        [':nid' => $node->id(), ':sid' => $siteId]
                    )
                    ->fetchField()
                ;

                if ($exists) {
                    return;
                }

                $layout = $storage->findForNodeOnSite($node->id(), $node->site_id);

                if ($layout) {
                    $clone = clone $layout;
                    $clone->setId(null);
                    $clone->setSiteId($siteId);

                    foreach ($clone->getAllRegions() as $region) {
                        $region->toggleUpdateStatus(true);
                    }

                    $storage->save($clone);
                }
            }
        }
    }
}
