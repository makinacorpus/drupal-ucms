<?php

namespace MakinaCorpus\Ucms\Seo\EventDispatcher;

use MakinaCorpus\Ucms\Site\Site;
use Symfony\Component\EventDispatcher\Event;

final class PathMatchEvent extends Event
{
    const EVENT_NAME = 'ucms_seo:path_match';

    private $site;
    private $path;
    private $realPath;

    public function __construct(Site $site, string $path)
    {
        $this->site = $site;
        $this->path = $path;
    }

    public function getSite(): Site
    {
        return $this->site;
    }

    /**
     * Get raw path from incomming requet (aka 'q' parameter).
     */
    public function getPath(): string
    {
        return $this->path ?? '';
    }

    /**
     * Set matched path
     */
    public function setMatchedPath(string $path)
    {
        $this->realPath = $path;
    }

    /**
     * @return null|string
     */
    public function getMatchedPath()
    {
        return $this->realPath;
    }
}
