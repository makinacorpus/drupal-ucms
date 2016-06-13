<?php

namespace MakinaCorpus\Ucms\Contrib\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Contrib\EventDispatcher\NodeEvent;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NodeEventSubscriber implements EventSubscriberInterface
{
    use StringTranslationTrait;


    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;


    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            NodeEvent::EVENT_SAVE => [
                ['onSave', 0]
            ],
        ];
    }


    /**
     * Constructor
     *
     * @param \DatabaseConnection $db
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(\DatabaseConnection $db, EventDispatcherInterface $eventDispatcher)
    {
        $this->db = $db;
        $this->eventDispatcher = $eventDispatcher;
    }


    public function onSave(NodeEvent $event)
    {
        $node = $event->getNode();
        
        $event = new NodeReferenceCollectEvent($node);
        $this->eventDispatcher->dispatch(NodeReferenceCollectEvent::EVENT_NAME, $event);

        // This will happen anyway, references are going to be rebuilt each save.
        $this->db
            ->delete('ucms_node_reference')
            ->condition('source_id', $node->id())
            ->execute();

        $references = $event->getReferences();

        if ($references) {
            // Proceed only if references are found.
            $q = $this->db
                ->insert('ucms_node_reference')
                ->fields(['source_id', 'target_id', 'type', 'field_name']);

            foreach ($references as $reference) {
                $q->values([
                    $reference->getSourceId(),
                    $reference->getTargetId(),
                    $reference->getType(),
                    $reference->getFieldName(),
                ]);
            }

            $q->execute();
        }
    }
}
