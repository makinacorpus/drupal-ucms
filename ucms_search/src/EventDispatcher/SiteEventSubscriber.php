<?php

namespace MakinaCorpus\Ucms\Search\EventDispatcher;

use MakinaCorpus\Ucms\Search\IndexStorage;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteAttachEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteCloneEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvents;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SiteEventSubscriber implements EventSubscriberInterface
{
    private $indexStorage;
    private $db;

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            SiteEvents::EVENT_ATTACH => [
                ['onAttach', 0]
            ],
            SiteEvents::EVENT_DETACH => [
                ['onDetach', 0]
            ],
            SiteEvents::EVENT_CLONE => [
                ['onClone', 10],
            ],
        ];
    }

    public function __construct(IndexStorage $indexStorage, \DatabaseConnection $db)
    {
        $this->indexStorage = $indexStorage;
        $this->db = $db;
    }

    private function reindexSite($siteId)
    {
        // First add the potentially missing nodes from the status table
        $this
            ->db
            ->query( // FIXME 'private' hardcoded
                "
                    INSERT INTO {ucms_search_status}
                        (nid, index_key, needs_reindex)
                    SELECT
                        sn.nid, 'private', 1
                    FROM {ucms_site_node} sn
                    WHERE
                        sn.site_id = ?
                        AND NOT EXISTS (
                            SELECT 1 FROM {ucms_search_status} ss
                            WHERE ss.nid = sn.nid
                        )
                ",
                [$siteId]
            )
        ;

        // Then update everyone to be reindexed
        switch ($this->db->driver()) {
            case 'mysql':
                $sql = "
                    UPDATE {ucms_search_status} ss
                    JOIN {ucms_site_node} sn ON sn.nid = ss.nid
                    SET ss.needs_reindex = 1
                    WHERE sn.site_id = ? AND ss.needs_reindex = 0
                ";
                break;
            default:
                $sql = "
                    UPDATE ucms_search_status AS ss
                    SET needs_reindex = 1
                    FROM ucms_site_node sn
                    WHERE sn.nid = ss.nid AND sn.site_id = 666 AND ss.needs_reindex = 0
                ";
                break;
        }
        $this->db->query($sql, [$siteId]);
    }

    private function reindexAllNodes($nidList)
    {
        if ($nidList) {
            if (count($nidList) < 50) { // Complètement au hasard (tm)
                $this->indexStorage->indexer()->enqueue($nidList);
            } else {
                $this->indexStorage->indexer()->bulkMarkForReindex($nidList);
            }
        }
    }

    public function onAttach(SiteAttachEvent $event)
    {
        $this->reindexAllNodes($event->getNodeIdList());
    }

    public function onDetach(SiteAttachEvent $event)
    {
        $this->reindexAllNodes($event->getNodeIdList());
    }

    public function onClone(SiteCloneEvent $event)
    {
        $this->reindexSite($event->getSite()->getId());
    }
}
