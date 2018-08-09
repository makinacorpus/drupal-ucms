<?php

namespace MakinaCorpus\Ucms\Tree\EventDispatcher;

use Drupal\Core\Database\Connection;
use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class NodeEventSubscriber implements EventSubscriberInterface
{
    private $database;

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            NodeEvent::EVENT_INSERT => [
                ['onInsert', 0],
            ],
            NodeEvent::EVENT_DELETE => [
                ['onDelete', 0],
            ],
        ];
    }

    /**
     * Default constructor.
     */
    public function __construct(Connection $database)
    {
        $this->database = $database;
    }

    public function onDelete(NodeEvent $event)
    {
        // @todo remove once foreign keys are restored
        $this
            ->database
            ->query("DELETE FROM {umenu_item} WHERE node_id = ?", [$event->getNode()->id()])
        ;
    }

    /**
     * On node duplicate update links in current site.
     */
    public function onInsertCloneUpdateMenus(NodeEvent $event)
    {
        $node = $event->getNode();

        // @todo fixme
        return;

        // When inserting a node, site_id is always the current site context.
        // Menu items that points to the original node within the site should
        // be replaced by menu items pointing to the new element. Instead of
        // polluting caches, we are just going to replace linked paths rather
        // seamlessly using a single SQL query, then silently drop menu items
        // cache (instead of deleting/saving new elements then clearing the
        // whole menu cache).
        if ($event->isClone() && $node->site_id) {
            switch ($this->database->driver()) {
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
            $this->database->query($sql, [
                ':route'  => 'node/' . $node->nid,
                ':site'   => $node->site_id,
                ':legacy' => 'node/' . $node->parent_nid,
            ]);

            // @todo Hardcoded procedural call, this is so wrong
            menu_cache_clear_all();
        }
    }
}
