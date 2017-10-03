<?php

namespace MakinaCorpus\Ucms\Group\NodeAccess;

use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeAccessEvent;
use MakinaCorpus\Ucms\Group\EventDispatcher\GroupContextEventTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * All context alterations events into the same subscriber, because it does
 * not mean anything to disable one or the other, it's all or nothing.
 */
class GroupContextSubscriber implements EventSubscriberInterface
{
    use GroupContextEventTrait;

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        // Priority here ensures it happens after the 'ucms_site' node event
        // subscriber, and that we will have all site information set
        return [
            NodeAccessEvent::EVENT_NODE_ACCESS => [
                ['onNodeAccess', 48],
            ],
        ];
    }

    /**
     * Checks node access for content creation
     */
    public function onNodeAccess(NodeAccessEvent $event)
    {
        $node = $event->getNode();

        if (Access::OP_CREATE === $event->getOperation()) {
            if (is_string($node)) {
                if ($group = $this->findMostRelevantGroup()) {
                    $allowedContentTypes = $group->getAttribute('allowed_content_types');
                    if ($allowedContentTypes && !in_array($node, $allowedContentTypes)) {
                        $event->deny();
                    }
                }
            }
        }

        return $event->ignore();
    }
}
