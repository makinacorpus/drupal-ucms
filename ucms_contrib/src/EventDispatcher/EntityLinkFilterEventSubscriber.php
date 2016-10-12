<?php


namespace MakinaCorpus\Ucms\Contrib\EventDispatcher;

use MakinaCorpus\Ucms\Site\NodeManager;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\ULink\EventDispatcher\EntityLinkFilterEvent;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EntityLinkFilterEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var SiteManager
     */
    private $siteManager;

    /**
     * @var NodeManager
     */
    private $nodeManager;


    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            EntityLinkFilterEvent::EVENT_BEFORE_FILTER => [
                ['processBeforeFilter', 500]
            ],
        ];
    }


    /**
     * Constructor.
     *
     * @param SiteManager $siteManager
     * @param NodeManager $nodeManager
     */
    public function __construct(SiteManager $siteManager, NodeManager $nodeManager)
    {
        $this->siteManager = $siteManager;
        $this->nodeManager = $nodeManager;
    }


    public function processBeforeFilter(EntityLinkFilterEvent $event)
    {
        if (!variable_get('ucms_contrib_clone_aware_features', false)) {
            return;
        }
        if (!$this->siteManager->hasContext()) {
            return;
        }

        $mapping = $this->nodeManager->getCloningMapping($this->siteManager->getContext());
        $uriInfo = &$event->getURIInfo();
        
        foreach ($uriInfo as &$info) {
            if (isset($mapping[$info['id']])) {
                $info['id'] = $mapping[$info['id']];
            }
        }
    }
}


