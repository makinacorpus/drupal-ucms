<?php

namespace MakinaCorpus\Ucms\Search\EventDispatcher;

use MakinaCorpus\Ucms\Search\NodeIndexerInterface;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This little bugger will send bulk node index at the end of the request
 */
class DequeueNodeEventSubscriber implements EventSubscriberInterface
{
    /**
     * {inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [KernelEvents::TERMINATE => 'onKernelTerminate'];
    }

    /**
     * @var NodeIndexerInterface
     */
    private $nodeIndexer;

    /**
     * Default constructor
     *
     * @param NodeIndexerInterface $nodeIndexer
     */
    public function __construct(NodeIndexerInterface $nodeIndexer)
    {
        $this->nodeIndexer = $nodeIndexer;
    }

    public function onKernelTerminate(Event $event)
    {
        $this->nodeIndexer->dequeue();
    }
}