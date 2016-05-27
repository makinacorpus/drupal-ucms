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
    const EVENT_CLONE     = 'node:clone';
    const EVENT_DELETE    = 'node:delete';

    /**
     * Constructor
     *
     * @param NodeInterface $node
     * @param int $userId
     * @param array $arguments
     */
    public function __construct(NodeInterface $node, $userId = null, array $arguments = [])
    {
        $arguments['uid'] = $userId;
        parent::__construct($node, $arguments);
    }

    /**
     * Who triggered this event.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->getArgument('uid');
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

