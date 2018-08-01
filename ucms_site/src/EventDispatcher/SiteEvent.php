<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use MakinaCorpus\Ucms\Site\Site;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Base implementation of site event
 */
class SiteEvent extends GenericEvent
{
    /**
     * Default constructor
     *
     * @param Site $site
     * @param int $userId
     * @param array $arguments
     */
    public function __construct(Site $site, $userId = null, array $arguments = [])
    {
        $arguments['uid'] = $userId;

        parent::__construct($site, $arguments);
    }

    /**
     * Who did this
     */
    public function getUserId(): int
    {
        return (int)$this->getArgument('uid');
    }

    /**
     * Get site
     */
    public function getSite(): Site
    {
        return $this->getSubject();
    }
}
