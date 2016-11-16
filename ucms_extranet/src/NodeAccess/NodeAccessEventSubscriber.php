<?php

namespace MakinaCorpus\Ucms\Extranet\NodeAccess;

use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeAccessEvent;
use MakinaCorpus\Ucms\Extranet\ExtranetAccess;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NodeAccessEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var SiteManager
     */
    private $siteManager;

    /**
     * Default constructor
     *
     * @param SiteManager $siteManager
     */
    public function __construct(SiteManager $siteManager)
    {
        $this->siteManager = $siteManager;
    }

    public function onNodeAccess(NodeAccessEvent $event)
    {
        if (!$this->siteManager->hasContext()) {
            return;
        }

        $account = $event->getAccount();
        $site = $this->siteManager->getContext();

        if (!$this->siteManager->getAccess()->userHasRole($account, $site, ExtranetAccess::ROLE_EXTRANET_CONTRIB)) {
            return;
        }

        $node = $event->getNode();
        $op = $event->getOperation();

        if ($op == 'create' && 'blogpost' === $node) {
            return $event->allow();
        }

        if ($op == 'update' && $node->site_id == $site->getId()) {
            if ('blogpost' === $node->type && $node->getOwnerId() == $account->id()) {
                return $event->allow();
            }
            if ('gallery' === $node->type) {
                return $event->allow();
            }
        }
    }
}
