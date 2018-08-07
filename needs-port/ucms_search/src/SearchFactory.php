<?php

namespace MakinaCorpus\Ucms\Search;

use Elasticsearch\Client;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class SearchFactory
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var IndexStorage
     */
    private $storage;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * Default constructor
     *
     * @param Client $client
     */
    public function __construct(Client $client, IndexStorage $storage, EventDispatcherInterface $dispatcher = null)
    {
        $this->client = $client;
        $this->storage = $storage;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Create a new search
     *
     * @param string $index
     *
     * @return Search
     */
    public function create($index)
    {
        $realname = $this->storage->getIndexRealname($index);

        $search = (new Search($this->client))->setIndex($realname);

        if ($this->dispatcher) {
            $this->dispatcher->dispatch('ucms_search.search_create', new GenericEvent($search));
        }

        return $search;
    }
}
