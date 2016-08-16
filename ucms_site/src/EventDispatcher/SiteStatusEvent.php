<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use MakinaCorpus\Ucms\Site\Site;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Event class for the alteration of the site's status.
 */
class SiteStatusEvent extends GenericEvent
{
    const EVENT_NAME = 'site:status_alter';

    /**
     * Site's status.
     *
     * @var integer
     */
    private $status;

    /**
     * Path concerned by the alteration.
     *
     * @var string
     */
    private $path;

    /**
     * Constructor.
     *
     * @param Site $site
     * @param integer $initialStatus
     * @param array $arguments
     */
    public function __construct(Site $site, $initialStatus, $path, array $arguments = [])
    {
        $this->status = $initialStatus;
        $this->path = $path;
        parent::__construct($site, $arguments);
    }

    /**
     * Get site.
     *
     * @return Site
     */
    public function getSite()
    {
        return $this->getSubject();
    }

    /**
     * Get the path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get the status.
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set the status.
     *
     * @param integer $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }
}
