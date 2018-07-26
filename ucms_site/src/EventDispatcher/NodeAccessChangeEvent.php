<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use Symfony\Component\EventDispatcher\GenericEvent;

final class NodeAccessChangeEvent extends GenericEvent
{
    const EVENT_NAME = 'ucms:node-access-change';

    private $nodeIdList = [];

    /**
     * Default constructor
     *
     * @param int[] $nodeIdList
     */
    public function __construct(array $nodeIdList)
    {
        $this->nodeIdList = $nodeIdList;
    }

    /**
     * Get node identifier list
     */
    public function getNodeIdList(): array
    {
        return $this->nodeIdList;
    }
}
