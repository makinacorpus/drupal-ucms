<?php

namespace MakinaCorpus\Ucms\Layout\EventDispatcher;


use MakinaCorpus\Ucms\Site\Site;
use Symfony\Component\EventDispatcher\GenericEvent;

class SiteEventListener
{
    /**
     * @var \DatabaseConnection
     */
    private $db;

    public function __construct(\DatabaseConnection $db)
    {
        $this->db = $db;
    }

    public function onSiteClone(GenericEvent $event)
    {
        /* @var Site */
        $source = $event->getArgument('source');
        /* @var Site */
        $target = $event->getSubject();

        // First copy node layouts
        $this
            ->db
            ->query(
                "
                INSERT INTO {ucms_layout} (site_id, nid)
                SELECT
                    :target, usn.nid
                FROM {ucms_layout} ul
                JOIN {ucms_site_node} usn ON
                    usn.nid = usn.nid
                    AND usn.site_id = :target
                WHERE
                    ul.site_id = :source
                    AND NOT EXISTS (
                        SELECT 1
                        FROM {ucms_layout} s_ul
                        WHERE
                            s_ul.nid = ul.nid
                            AND s_ul.site_id = :target3
                    )
            ",
                [
                    ':target'  => $target->getId(),
                    ':target2' => $target->getId(),
                    ':source'  => $source->getId(),
                    ':target3' => $target->getId(),
                ]
            );

        // Then duplicate layout data
        $this
            ->db
            ->query(
                "
                INSERT INTO {ucms_layout_data}
                    (layout_id, region, nid, weight, view_mode)
                SELECT
                    target_ul.id,
                    uld.region,
                    uld.nid,
                    uld.weight,
                    uld.view_mode
                FROM {ucms_layout} source_ul
                JOIN {ucms_layout_data} uld ON
                    source_ul.id = uld.layout_id
                    AND source_ul.site_id = :source
                JOIN {node} n ON n.nid = uld.nid
                JOIN {ucms_layout} target_ul ON
                    target_ul.nid = uld.nid
                    AND target_ul.site_id = :target
                WHERE
                    (n.status = 1 OR n.is_global = 0)
            ",
                [
                    ':source' => $source->getId(),
                    ':target' => $target->getId(),
                ]
            );

    }
}
