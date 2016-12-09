<?php

namespace MakinaCorpus\Ucms\Label\EventDispatcher;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\APubSub\Notification\EventDispatcher\ResourceEvent;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NodeEventSubscriber implements EventSubscriberInterface
{
    use StringTranslationTrait;


    /**
     * @var EntityManager
     */
    protected $entityManager;


    /**
     * Constructor
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }


    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'node:add' => [
                ['onNodeAdd', 0],
            ],
//            'node:edit' => [
//                ['onNodeEdit', 0],
//            ],
//            'node:delete' => [
//                ['onNodeDelete', 0],
//            ],
            'node:new_labels' => [
                ['onNodeNewLabels', 0],
            ],
        ];
    }


    /**
     * node:add events handler method.
     *
     * @param ResourceEvent $event
     */
    public function onNodeAdd(ResourceEvent $event)
    {
        $this->addLabelChannels($event);
    }


    /**
     * node:edit events handler method.
     *
     * @param ResourceEvent $event
     */
    public function onNodeEdit(ResourceEvent $event)
    {
        $this->addLabelChannels($event);
    }


    /**
     * node:delete events handler method.
     *
     * @param ResourceEvent $event
     */
    public function onNodeDelete(ResourceEvent $event)
    {
        $this->addLabelChannels($event);
    }


    /**
     * node:new_labels events handler method.
     *
     * @param ResourceEvent $event
     */
    public function onNodeNewLabels(ResourceEvent $event)
    {
        $event->ignoreDefaultChan();
        foreach ($event->getArgument('new_labels') as $labelId) {
            $event->addResourceChanId('label', $labelId);
        }
    }


    /**
     * Adds specific channels of the node's labels to the event.
     *
     * @param ResourceEvent $event
     */
    protected function addLabelChannels(ResourceEvent $event)
    {
        $nodeId = $event->getResourceIdList()[0];
        $node   = $this->entityManager->getStorage('node')->load($nodeId);
        $items  = field_get_items('node', $node, 'labels');

        if ($items) {
            foreach ($items as $item) {
                $event->addResourceChanId('label', $item['tid']);
            }
        }
    }
}


