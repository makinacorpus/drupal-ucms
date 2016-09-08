<?php

namespace MakinaCorpus\Ucms\Layout\EventDispatcher;

use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeEvent;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class NodeEventSubscriber implements EventSubscriberInterface
{
    private $db;
    private $manager;

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            NodeEvent::EVENT_INSERT => [
                ['onInsert', -10]
            ],
        ];
    }

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param SiteManager $manager
     */
    public function __construct(\DatabaseConnection $db, SiteManager $manager)
    {
        $this->db = $db;
        $this->manager = $manager;
    }

    /**
     * When cloning a node within a site, we must replace all its parent
     * references using the new new node identifier instead, in order to make
     * it gracefully inherit from the right layouts.
     */
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
                // by the clone instead of the parent, IF AND ONLY IF the site
                // is the same; please note that the dereferencing happens in
                // 'ucms_site' module.
                $this
                    ->db
                    ->query(
                        "UPDATE {ucms_layout} SET nid = :clone WHERE nid = :parent AND site_id = :site",
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
                switch ($this->db->driver()) {

                    case 'mysql':
                        $sql = "
                            UPDATE {ucms_layout_data} d
                            JOIN {ucms_layout} l ON l.id = d.layout_id
                            SET
                                d.nid = :clone
                            WHERE
                                d.nid = :parent
                                AND l.site_id = :site
                        ";
                        break;

                    default:
                        $sql = "
                            UPDATE {ucms_layout_data} AS d
                            SET
                                nid = :clone
                            FROM {ucms_layout} l
                            WHERE
                                l.id = d.layout_id
                                AND d.nid = :parent
                                AND l.site_id = :site
                        ";
                        break;
                }

                $this->db->query($sql, [
                    ':clone'  => $node->id(),
                    ':parent' => $node->parent_nid,
                    ':site'   => $node->site_id,
                ]);
            }
        }
    }
}
