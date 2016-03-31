<?php


namespace MakinaCorpus\Ucms\Label\EventDispatcher;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\APubSub\Notification\EventDispatcher\ResourceEvent;


class NodeEventListener
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


