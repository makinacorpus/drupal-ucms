<?php


namespace MakinaCorpus\Ucms\Contrib\EventDispatcher;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Contrib\EventDispatcher\NodeEvent;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class NodeEventSubscriber implements EventSubscriberInterface
{
    use StringTranslationTrait;


    /**
     * @var \DatabaseConnection
     */
    private $db;

//    /**
//     * @var SiteManager
//     */
//    private $manager;
//
//    /**
//     * @var NodeManager
//     */
//    private $nodeManager;
//
//    /**
//     * @var EntityManager
//     */
//    private $entityManager;
//
//    /**
//     * @var EventDispatcherInterface
//     */
//    private $eventDispatcher;


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
            NodeEvent::EVENT_CLONE => [
                ['onClone', 0]
            ],
        ];
    }


    /**
     * Constructor
     *
     * @param \DatabaseConnection $db
     * @param SiteManager $manager
     * @param SiteManager $nodeManager
     * @param EntityManager $entityManager
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        \DatabaseConnection $db
//        SiteManager $manager,
//        NodeManager $nodeManager,
//        EntityManager $entityManager,
//        EventDispatcherInterface $eventDispatcher
    ) {
        $this->db = $db;
//        $this->manager = $manager;
//        $this->nodeManager = $nodeManager;
//        $this->entityManager = $entityManager;
//        $this->eventDispatcher= $eventDispatcher;
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
        }
    }


    public function onUpdate(NodeEvent $event)
    {
        $node = $event->getNode();
        ucms_contrib_node_collect_reference($node);
    }


    public function onClone(NodeEvent $event)
    {
        $node = $event->getNode();
    }
}

