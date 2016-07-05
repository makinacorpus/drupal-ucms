<?php

namespace MakinaCorpus\Ucms\Tree\EventDispatcher;

use MakinaCorpus\Ucms\Contrib\EventDispatcher\NodeEvent;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NodeEventSubscriber implements EventSubscriberInterface
{
    private $db;

    static public function getSubscribedEvents()
    {
        return [
            NodeEvent::EVENT_INSERT => [
                ['onInsert', 0],
            ],
        ];
    }

    public function __construct(\DatabaseConnection $db)
    {
        $this->db = $db;
    }

    public function onInsert(NodeEvent $event)
    {
        $node = $event->getNode();

        // When inserting a node, site_id is always the current site context.
        // Menu items that points to the original node within the site should
        // be replaced by menu items pointing to the new element. Instead of
        // polluting caches, we are just going to replace linked paths rather
        // seamlessly using a single SQL query, then silently drop menu items
        // cache (instead of deleting/saving new elements then clearing the
        // whole menu cache).
        if ($event->isClone() && $node->site_id) {
            switch ($this->db->driver()) {
                case 'mysql':
                    $sql = "
                        UPDATE {menu_links} ml
                        JOIN {umenu} u ON u.name = ml.menu_name
                        SET link_path = :route
                        WHERE u.site_id = :site AND ml.link_path = :legacy
                    ";
                    break;
                default:
                    $sql = "
                        UPDATE {menu_links} AS ml
                        SET link_path = :route
                        FROM {umenu} u
                        WHERE u.name = ml.menu_name AND u.site_id = :site AND ml.link_path = :legacy
                    ";
                    break;
            }
            $this->db->query($sql, [
                ':route'  => 'node/' . $node->nid,
                ':site'   => $node->site_id,
                ':legacy' => 'node/' . $node->parent_nid,
            ]);

            // @todo Hardcoded procedural call, this is so wrong
            menu_cache_clear_all();
        }
    }
}
