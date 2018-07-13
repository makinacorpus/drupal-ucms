<?php

namespace MakinaCorpus\Ucms\Contrib\EventDispatcher;

use MakinaCorpus\Ucms\Site\EventDispatcher\NodeReferenceCollectEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NodeEventSubscriber implements EventSubscriberInterface
{
    private $database;

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            NodeReferenceCollectEvent::EVENT_NAME => [
                ['onNodeReferenceCollect', -4096], // Must be the last
            ],
        ];
    }

    public function __construct(\DatabaseConnection $database)
    {
        $this->database = $database;
    }

    public function onNodeReferenceCollect(NodeReferenceCollectEvent $event)
    {
        $node = $event->getNode();

        // This will happen anyway, references are going to be rebuilt each save.
        $this->database->delete('ucms_node_reference')->condition('source_id', $node->id())->execute();

        if ($references = $event->getReferences()) {
            // Proceed only if references are found.
            $query = $this->database->insert('ucms_node_reference')->fields(['source_id', 'target_id', 'type', 'field_name', 'ts_touched']);
            $now = (new \DateTime())->format('Y-m-d H:i:s');

            foreach ($references as $reference) {
                $query->values([
                    $reference->getSourceId(),
                    $reference->getTargetId(),
                    $reference->getType(),
                    $reference->getFieldName(),
                    $now
                ]);
            }

            $query->execute();
        }
    }
}
