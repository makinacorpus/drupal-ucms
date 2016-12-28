<?php

namespace MakinaCorpus\Ucms\Layout\EventDispatcher;

use MakinaCorpus\Ucms\Contrib\EventDispatcher\BehaviorCollectionEvent;
use MakinaCorpus\Ucms\Contrib\Behavior\ContentTypeBehavior as Behavior;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BehaviorCollectionEventSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            BehaviorCollectionEvent::EVENT_NAME => [
                ['onCollect', 0],
            ],
        ];
    }

    /**
     * @param BehaviorCollectionEvent $event
     */
    public function onCollect(BehaviorCollectionEvent $event)
    {
        $event->addBehavior((new Behavior('droppable_in_layout'))
            ->setName("Peut être glissé-déposé dans les mises en page")
            ->setDescription("Les contenus pourront être glissés-déposés du panier vers les mises en page (composition de page).")
        );
    }
}
