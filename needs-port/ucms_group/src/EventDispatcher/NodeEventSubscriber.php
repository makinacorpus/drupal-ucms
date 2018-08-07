<?php

namespace MakinaCorpus\Ucms\Group\EventDispatcher;

use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeEvent;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Add group functionnality on nodes
 */
class NodeEventSubscriber implements EventSubscriberInterface
{
    use GroupContextEventTrait;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            NodeEvent::EVENT_PREPARE => [
                ['onNodePrepare', 0]
            ],
            NodeEvent::EVENT_PRESAVE => [
                ['onNodePresave', 0]
            ],
        ];
    }

    /**
     * Sets the most relevant 'group_id' and 'is_ghost' property values
     */
    public function onNodePrepare(NodeEvent $event)
    {
        $node = $event->getNode();

        if (!empty($node->group_id)) {
            return; // Someone took care of this for us
        }

        $node->group_id = $this->findMostRelevantGroupId();
        $node->is_ghost = (int)$this->findMostRelevantGhostValue($node);
    }

    /**
     * Prepare hook is no always called, this is why we do reproduce what does
     * happen during the prepare hook in the presave hook, if no value has
     * already been provided
     */
    public function onNodePresave(NodeEvent $event)
    {
        $node = $event->getNode();

        // When coming from the node form, node form has already been submitted
        // case in which, if relevant, a group identifier has already been set
        // and this code won't be execute. In the other hand, if the prepare
        // hook has not been invoked, this will run and set things right.
        // There is still a use case where the node comes from the node form but
        // there is no contextual group, case in which this code will wrongly
        // run, but hopefuly since it is just setting defaults, it won't change
        // the normal behavior.
        if (empty($node->group_id)) {
            $groupId = $this->findMostRelevantGroupId();

            if ($groupId) {
                $node->group_id = $groupId;
            }

            $node->is_ghost = $this->findMostRelevantGhostValue($node);
        }
    }
}
