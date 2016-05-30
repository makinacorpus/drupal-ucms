<?php


namespace MakinaCorpus\Ucms\Contrib\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Contrib\EventDispatcher\NodeEvent;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class NodeEventSubscriber implements EventSubscriberInterface
{
    use StringTranslationTrait;


    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * @var SiteManager
     */
    private $siteManager;


    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            NodeEvent::EVENT_INSERT => [
                ['onInsert', 0]
            ],
            NodeEvent::EVENT_UPDATE => [
                ['onUpdate', 0]
            ],
        ];
    }


    /**
     * Constructor
     *
     * @param \DatabaseConnection $db
     * @param SiteManager $siteManager
     */
    public function __construct(\DatabaseConnection $db, SiteManager $siteManager)
    {
        $this->db = $db;
        $this->siteManager = $siteManager;
    }


    public function onInsert(NodeEvent $event)
    {
        $node = $event->getNode();
        ucms_contrib_node_collect_reference($node);

        if (isset($node->parent_nid)) {
            // Menu path is handled by menu_node_insert().

            // Update layout
            $this->db
                ->update('ucms_layout')
                ->fields(['nid' => $node->nid])
                ->condition('site_id', $node->site_id)
                ->condition('nid', $node->parent_nid)
                ->execute()
            ;

            // Dereference original node
            $this->db
                ->delete('ucms_site_node')
                ->condition('site_id', $node->site_id)
                ->condition('nid', $node->parent_nid)
                ->execute()
            ;

            // Define the node as the homepage of its site
            // if the parent node was the homepage.
            $site = $this->siteManager->getStorage()->findOne($node->site_id);
            if ($site->home_nid == $node->parent_nid) {
                $site->home_nid = $node->nid;
                $this->siteManager->getStorage()->save($site);
            }
        }
    }


    public function onUpdate(NodeEvent $event)
    {
        $node = $event->getNode();
        ucms_contrib_node_collect_reference($node);
    }
}

