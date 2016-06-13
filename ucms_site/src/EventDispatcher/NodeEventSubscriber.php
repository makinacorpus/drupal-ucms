<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Contrib\EventDispatcher\NodeEvent;
use MakinaCorpus\Ucms\Contrib\EventDispatcher\NodeCollectionEvent;
use MakinaCorpus\Ucms\Site\NodeManager;
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

    /**
     * @var SiteManager
     */
    private $manager;

    /**
     * @var NodeManager
     */
    private $nodeManager;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            NodeCollectionEvent::EVENT_LOAD => [
                ['onLoad', 0]
            ],
            NodeEvent::EVENT_PREPARE => [
                ['onPrepare', 0]
            ],
            NodeEvent::EVENT_PREINSERT => [
                ['onPreInsert', 0]
            ],
            NodeEvent::EVENT_INSERT => [
                ['onInsert', 0],
                ['onPostInsert', -32],
            ],
            NodeEvent::EVENT_SAVE => [
                ['onSave', 0]
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
        \DatabaseConnection $db,
        SiteManager $manager,
        NodeManager $nodeManager,
        EntityManager $entityManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->db = $db;
        $this->manager = $manager;
        $this->nodeManager = $nodeManager;
        $this->entityManager = $entityManager;
        $this->eventDispatcher= $eventDispatcher;
    }

    public function onPrepare(NodeEvent $event)
    {
        $node = $event->getNode();

        if ($node->isNew()) {
            // Adds all custom fields of the nodes table as properties of the node object
            $initial_schema = drupal_get_schema_unprocessed('node', 'node');
            $current_schema = drupal_get_schema('node');

            $custom_fields = array_diff_key($current_schema['fields'], $initial_schema['fields']);

            foreach ($custom_fields as $name => $info) {
                if (!isset($node->$name)) {
                    $node->$name = isset($info['default']) ? $info['default'] : null;
                }
            }

            // Initializes the ucms_sites property
            $node->ucms_sites = [];
        }
    }

    public function onLoad(NodeCollectionEvent $event)
    {
        $nodes = $event->getNodes();

        // Attach site identifiers list to each node being loaded. Althought it does
        // and extra SQL query, this being the core of the whole site business, we
        // can't bypass this step nor risk it being stalled with unsynchronized data
        // in cache.

        // @todo Later in the future, determiner an efficient way of caching it,
        // we'll need this data to be set in Elastic Search anyway so we'll risk data
        // stalling in there.
        $r = $this
            ->db
            ->select('ucms_site_node', 'usn')
            ->fields('usn', ['nid', 'site_id'])
            ->condition('usn.nid', array_keys($nodes))
            ->orderBy('usn.nid')
            ->execute()
        ;

        foreach ($nodes as $node) {
            $node->ucms_sites = [];
        }
        foreach ($r as $row) {
            $node = $nodes[$row->nid];
            $node->ucms_sites[] = $row->site_id;
        }

        // Repeat it with user visible sites allowing administration pages to
        // compute visible node links for the user, that's why we restrict this
        // extra query to master site only
        if (!$this->manager->hasContext()) {

            $r = $this
                ->db
                ->select('ucms_site_node', 'usn')
                ->fields('usn', ['nid', 'site_id'])
                ->condition('usn.nid', array_keys($nodes))
                ->orderBy('usn.nid')
                ->addTag('ucms_site_access')
                ->execute()
            ;

            foreach ($nodes as $node) {
                $node->ucms_allowed_sites = [];
            }
            foreach ($r as $row) {
                $node = $nodes[$row->nid];
                $node->ucms_allowed_sites[] = $row->site_id;
            }
        }
    }

    public function onPreInsert(NodeEvent $event)
    {
        $node = $event->getNode();

        if (!property_exists($node, 'ucms_sites')) {
            $node->ucms_sites = [];
        }

        if (!property_exists($node, 'site_id')) {
            $node->site_id = null;
        }

        // If the node is created in a specific site context, then gives the
        // node ownership to this site, note this might change in the end, it
        // is mostly the node original site.
        if (!empty($node->site_id)) {
            $node->ucms_sites[] = $node->site_id;
            $node->is_global = 0;
        } else if (false !== $node->site_id) {
            if ($site = $this->manager->getContext()) {

                $node->site_id = $site->id;

                // This will happen in case a node is cloned.
                if (!in_array($site->id, $node->ucms_sites)) {
                    $node->ucms_sites[] = $site->id;
                }
                $node->is_global = 0;
            } else {
                $node->is_global = 1;
            }
        } else {
            $node->is_global = 1;
        }
    }

    public function onInsert(NodeEvent $event)
    {
        $node = $event->getNode();

        if (!empty($node->site_id)) {
            $site = $this->manager->getStorage()->findOne($node->site_id);

            $this->nodeManager->createReference($site, $node);

            // Site might not have an homepage, because the factory wasn't
            // configured properly before creation, so just set this node
            // as home page.
            if (empty($site->home_nid)) {
                $site->home_nid = $node->nid;
                $this->manager->getStorage()->save($site, ['home_nid']);
            }
        }

        if ($event->isClone() && $node->site_id) {

            $storage = $this->manager->getStorage();
            $site = $storage->findOne($node->site_id);

            // Replace the new node home page if the original was the home page
            if ($site->getHomeNodeId() == $node->parent_nid) {
                $site->setHomeNodeId($node->id());
                $storage->save($site, ['home_nid']);

                drupal_set_message(t("The site home page has been changed to the current content."), 'success');

                if (!$node->isPublished()) {
                    drupal_set_message(t("The cloned content is now the current site home page, yet it is unpublished, you should probably publish it!"), 'warning');
                }
            }
        }
    }

    public function onPostInsert(NodeEvent $event)
    {
        $node = $event->getNode();

        // This MUST happen after the layouts are being changed, else the
        // foreign key cascade constraints will happen and nothing will be
        // duplicated
        if ($event->isClone() && $node->site_id && $node->parent_nid) {
            // Dereference node from the clone site, since it will be replaced
            // by the new one in all contextes it can be
            $this->nodeManager->deleteReferenceBulkFromSite($node->site_id, [$node->parent_nid]);
        }
    }

    public function onSave(NodeEvent $event)
    {
        $node = $event->getNode();
        if ($node->ucms_sites) {
            $this->nodeManager->createReferenceBulkForNode($node->id(), $node->ucms_sites);
        }
    }

    public function onDelete(NodeEvent $event)
    {
        // We do not need to delete from the {ucms_site_node} table since it's
        // being done by ON DELETE CASCADE deferred constraints
    }
}
