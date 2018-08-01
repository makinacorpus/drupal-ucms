<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use MakinaCorpus\Ucms\Site\Site;
use Symfony\Component\EventDispatcher\GenericEvent;

class SiteStatusEvent extends GenericEvent
{
    const EVENT_NAME = 'site:status_alter';

    private $status;
    private $path;

    /**
     * Constructor.
     */
    public function __construct(Site $site, int $initialStatus, string $path, array $arguments = [])
    {
        $this->status = $initialStatus;
        $this->path = $path;

        parent::__construct($site, $arguments);
    }

    /**
     * Get site
     */
    public function getSite(): Site
    {
        return $this->getSubject();
    }

    /**
     * Get the path
     */
    public function getPath(): string
    {
        return $this->path ?? '';
    }

    /**
     * Get the status.
     */
    public function getStatus(): int
    {
        return (int)$this->status;
    }

    /**
     * Set the status
     */
    public function setStatus(int $status)
    {
        $this->status = $status;
    }
}
