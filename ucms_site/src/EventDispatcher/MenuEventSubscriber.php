<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Umenu\MenuEnvEvent;
use MakinaCorpus\Umenu\TreeManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MenuEventSubscriber implements EventSubscriberInterface
{
    private $siteManager;
    private $treeManager;

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            MenuEnvEvent::EVENT_FINDTREE => [
                ['onMenuFindTree', 0]
            ],
        ];
    }

    /**
     * Constructor
     */
    public function __construct(SiteManager $siteManager, TreeManager $treeManager)
    {
        $this->siteManager = $siteManager;
        $this->treeManager = $treeManager;
    }

    /**
     * On menu find tree, restrict using site context.
     */
    public function onMenuFindTree(MenuEnvEvent $event)
    {
        if (!$this->siteManager->hasContext()) {
            $event->addCondition('site_id', -1);
            return;
        }

        $event->addCondition('site_id', $this->siteManager->getContext()->getId());
    }
}
