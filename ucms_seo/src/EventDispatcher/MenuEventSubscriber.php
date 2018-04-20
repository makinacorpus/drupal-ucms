<?php

namespace MakinaCorpus\Ucms\Seo\EventDispatcher;

use MakinaCorpus\Ucms\Seo\SeoService;
use MakinaCorpus\Ucms\Tree\EventDispatcher\MenuEvent;
//use MakinaCorpus\Umenu\Event\MenuEvent as UMenuEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MenuEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var SeoService
     */
    private $service;

    /**
     * Default constructor
     *
     * @param SeoService $service
     */
    public function __construct(SeoService $service)
    {
        $this->service = $service;
    }

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            MenuEvent::EVENT_TREE => [
                ['onMenuTree', 0],
            ],
            /*
            UMenuEvent::EVENT_DELETE => [
                ['onMenuChange', 0],
            ],
            UMenuEvent::EVENT_TOGGLE_MAIN => [
                ['onMenuChange', 0],
            ],
            UMenuEvent::EVENT_UPDATE => [
                ['onMenuChange', 0],
            ],
             */
        ];
    }

    /**
     * On site context initialization.
     */
    public function onMenuTree(MenuEvent $event)
    {
        $refresh = [];

        foreach ($event->getDeletedItems() as $item) {
            $refresh[] = $item->getNodeId();
        }
        foreach ($event->getTree()->getChildren() as $item) {
            $refresh[] = $item->getNodeId();
        }

        if ($refresh) {
            $this->service->onAliasChange($refresh);
        }
    }

    /**
     * UMenu module menu change
     *
    public function onMenuChange(UMenuEvent $event)
    {
        $this->service->onMenuChange($event->getMenu());
    }
     */
}
