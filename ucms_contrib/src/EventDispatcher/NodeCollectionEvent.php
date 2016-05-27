<?php


namespace MakinaCorpus\Ucms\Contrib\EventDispatcher;

use Drupal\node\NodeInterface;

use Symfony\Component\EventDispatcher\GenericEvent;


class NodeCollectionEvent extends GenericEvent
{
    const EVENT_LOAD = 'node:load';

    /**
     * Constructor
     *
     * @param NodeInterface[] $nodes
     * @param int $userId
     * @param array $arguments
     */
    public function __construct(array $nodes, $userId = null, array $arguments = [])
    {
        $arguments['uid'] = $userId;
        parent::__construct($nodes, $arguments);
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
     * Get the nodes.
     *
     * @return NodeInterface[]
     */
    public function getNodes()
    {
        return $this->getSubject();
    }
}

