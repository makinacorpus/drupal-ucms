<?php

namespace MakinaCorpus\Ucms\Contrib\EventDispatcher;

use Drupal\node\NodeInterface;

use Symfony\Component\EventDispatcher\GenericEvent;

class NodeEvent extends GenericEvent
{
    const EVENT_PREPARE   = 'node:prepare';
    const EVENT_PREINSERT = 'node:preinsert';
    const EVENT_PREUPDATE = 'node:preupdate';
    const EVENT_PRESAVE   = 'node:presave';
    const EVENT_INSERT    = 'node:insert';
    const EVENT_UPDATE    = 'node:update';
    const EVENT_SAVE      = 'node:save';
    const EVENT_DELETE    = 'node:delete';

    private $eventName;
    private $userId;

    /**
     * Constructor
     *
     * @param string $eventName
     * @param NodeInterface $node
     * @param int $userId
     * @param array $arguments
     */
    public function __construct($eventName, NodeInterface $node, $userId = null)
    {
        $this->eventName = $eventName;
        $this->userId = $userId;

        // Argument here serves only for the notification listeners, keep this.
        parent::__construct($node, ['uid' => $userId]);
    }

    /**
     * Is the current event a clone operation
     *
     * @return boolean
     */
    public function isClone()
    {
        return self::EVENT_INSERT === $this->eventName && null !== $this->getNode()->parent_nid;
    }

    /**
     * Who triggered this event.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Get the node.
     *
     * @return NodeInterface
     */
    public function getNode()
    {
        return $this->getSubject();
    }
}
