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
        // On 08/09/2016, I stumbled upon this piece of code. Long story short:
        // when you reference a node in a site, it duplicates the original site
        // layout in the new site.
        //
        // While I could see some kind of use for this, I am not sure this is
        // really necessary, moreover, when you save a layout, it triggers the
        // node attach event, and run this in a potentially infinite loop.
        //
        // I am quite sure that the original wanted behavior was on node clone
        // and not on node reference: when you want to edit a node that's not
        // yours, on your site, the application propose that you may clone it on
        // the site instead of editing the original node, at this exact point in
        // time, you do need to duplicate layouts.
        //
        // Sad story about this code is when it attempts to get the current node
        // storage, it resets the layout context internal token, which makes it
        // angry and throw exception.
        //
        // This is why, at this very exact moment, I am not going to create any
        // regression, and let this code live, I'll just put a failsafe that'll
        // deactivate it during a layout save.
        //
        // See the next line. And prey. Or yell. Or just get over it.
        if ($this->contextManager->isInEditMode()) {
            return;
        }

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
                // cloning a node, the layout data already has been inserted
                // if the original was existing).
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
