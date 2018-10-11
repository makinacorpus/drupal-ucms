<?php

namespace MakinaCorpus\Ucms\Seo\EventDispatcher;

use Symfony\Component\EventDispatcher\Event;

class NodePathAliasComputeEvent extends Event
{
    const EVENT_NAME = 'ucms_seo:path_alias_compute';

    private $nodeId;
    private $siteId;
    private $segment;
    private $path;

    public function __construct(int $node, int $siteId, string $segment, string $path)
    {
        $this->nodeId = $node;
        $this->siteId = $siteId;
        $this->segment = $segment;
        $this->path = $path;
    }

    public function getComputedPathAlias(): string
    {
        return $this->path ?? '';
    }

    public function getNodeSegment(): string
    {
        return $this->segment ?? '';
    }

    public function getNodeId(): int
    {
        return $this->nodeId ?? 0;
    }

    public function getSiteId(): int
    {
        return $this->siteId ?? 0;
    }

    public function setNodeAlias(string $path)
    {
        $this->path = $path;
    }
}
