<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use MakinaCorpus\Ucms\Site\Site;

class SiteAttachEvent extends SiteEvent
{
    /**
     * Default constructor
     *
     * @param Site $site
     * @param int[] $nodeIdList
     * @param int $userId
     * @param array $arguments
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
     *
     * @return int[]
     */
    public function getSiteIdList()
    {
        return $this->getArgument('sites');
    }

    /**
     * Get attached or dettached node identifier list
     *
     * @return int[]
     */
    public function getNodeIdList()
    {
        return $this->getArgument('nodes');
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
    public function getSite()
    {
        throw new \LogicException("This event works on multiple sites and should, getSite() cannot be called");
    }
}
