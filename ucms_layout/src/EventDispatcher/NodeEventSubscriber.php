<?php

namespace MakinaCorpus\Ucms\Layout\EventDispatcher;

use MakinaCorpus\Ucms\Contrib\EventDispatcher\NodeEvent;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NodeEventSubscriber implements EventSubscriberInterface
{
    private $db;
    private $manager;

    static public function getSubscribedEvents()
    {
        return [
            NodeEvent::EVENT_INSERT => [
                ['onInsert', 0]
            ],
        ];
    }

    public function __construct(\DatabaseConnection $db, SiteManager $manager)
    {
        $this->db = $db;
        $this->manager = $manager;
    }

    public function onInsert(NodeEvent $event)
    {
        $node = $event->getNode();

        // When inserting a node, site_id is always the current site context.
        if ($event->isClone() && $node->site_id) {

            $exists = (bool)$this
                ->db
                ->query(
                    "SELECT 1 FROM {ucms_site_node} WHERE nid = :nid AND site_id = :sid",
                    [':nid' => $node->parent_nid, ':sid' => $node->site_id]
                )
            ;

            if ($exists) {

                // On clone, the original node layout should be kept but owned
                // by the clone instead of the parent, IF AND ONLY If the site
                // is the same; please note that the dereferencing happens in
                // 'ucms_site' module.
                $this
                    ->db
                    ->query(
                        "UPDATE {ucms_layout} l SET l.nid = :clone WHERE l.nid = :parent AND l.site_id = :site",
                        [
                            ':clone'  => $node->id(),
                            ':parent' => $node->parent_nid,
                            ':site'   => $node->site_id,
                        ]
                    )
                ;

                // The same way, if the original node was present in some site
                // layout, it must be replaced by the new one, IF AND ONLY IF
                // the site is the same
                $this
                    ->db
                    ->query(
                        "
                            UPDATE {ucms_layout_data} d
                            JOIN {ucms_layout} l ON l.id = d.layout_id
                            SET
                                d.nid = :clone
                            WHERE
                                d.nid = :parent
                                AND l.site_id = :site",
                        [
                            ':clone'  => $node->id(),
                            ':parent' => $node->parent_nid,
                            ':site'   => $node->site_id,
                        ]
                    )
                ;
            }
        }
    }
}
