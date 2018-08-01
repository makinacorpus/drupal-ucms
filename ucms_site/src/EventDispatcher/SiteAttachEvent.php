<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use MakinaCorpus\Ucms\Site\Site;

class SiteAttachEvent extends SiteEvent
{
    /**
     * Default constructor
     */
    public function __construct($siteIdList, $nodeIdList, $userId = null, array $arguments = [])
    {
        if (!is_array($nodeIdList)) {
            $nodeIdList = [$nodeIdList];
        }
        if (!is_array($siteIdList)) {
            $siteIdList = [$siteIdList];
        }

        // Fake a site instance, it won't be utilized anyway
        parent::__construct(new Site(), $userId, ['sites' => $siteIdList, 'nodes' => $nodeIdList] + $arguments);
    }

    /**
     * Get attached or dettached site identifier list
     */
    public function getSiteIdList(): array
    {
        return (array)$this->getArgument('sites');
    }

    /**
     * Get attached or dettached node identifier list
     */
    public function getNodeIdList(): array
    {
        return (int)$this->getArgument('nodes');
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        throw new \LogicException("This event works on multiple sites and should, getSite() cannot be called");
    }

    /**
     * {@inheritdoc}
     */
    public function getSite(): Site
    {
        throw new \LogicException("This event works on multiple sites and should, getSite() cannot be called");
    }
}
