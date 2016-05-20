<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use MakinaCorpus\Ucms\Site\Site;

class SiteAttachEvent extends SiteEvent
{
    const EVENT_ATTACH = 'site:node.attach';
    const EVENT_DETACH = 'site:node.dettach';

    /**
     * Default constructor
     *
     * @param Site $site
     * @param int[] $nodeIdList
     * @param int $userId
     * @param array $arguments
     */
    public function __construct(Site $site, $nodeIdList, $userId = null, array $arguments = [])
    {
        if (!is_array($nodeIdList)) {
            $nodeIdList = [$nodeIdList];
        }

        parent::__construct($site, $userId, ['nodes' => $nodeIdList] + $arguments);
    }

    /**
     * Get attached or dettached node identifier list
     *
     * @return int[]
     */
    public function getNodeList()
    {
        return $this->getArgument('nodes');
    }
}
