<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\APubSub\Notification\EventDispatcher\ResourceEvent;
use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeCollectionEvent;
use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeEvent;
use MakinaCorpus\Ucms\Site\NodeManager;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;
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
     * @var bool
     */
    private $nodeReferenceAll;

    /**
     * @var null|string[]
     */
    private $nodeReferenceWhitelist;

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
                ['onPrepare', 128]
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
            NodeEvents::ACCESS_CHANGE => [
                ['onNodeAccessChange', 0],
            ],
            NodeReferenceCollectEvent::EVENT_NAME => [
                ['onNodeReferenceCollect', 0],
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
     * @param null|bool $nodeReferenceAll
     *   If null, use the ucms_site_node_reference_all variable.
     * @param null|string[] $nodeReferenceWhitelist
     *   If null, use the ucms_site_node_reference_whitelist variable.
     *   Ignored if $nodeReferenceAll is true. This is a list of field
     *   names.
     */
    public function __construct(
        \DatabaseConnection $db,
        SiteManager $manager,
        NodeManager $nodeManager,
        EntityManager $entityManager,
        EventDispatcherInterface $eventDispatcher,
        $nodeReferenceAll = null,
        $nodeReferenceWhitelist = null
    ) {
        $this->db = $db;
        $this->manager = $manager;
        $this->nodeManager = $nodeManager;
        $this->entityManager = $entityManager;
        $this->eventDispatcher= $eventDispatcher;

        if (null === $nodeReferenceAll) {
            $nodeReferenceAll = variable_get('ucms_site_node_reference_all', true);
        }
        $this->nodeReferenceAll = (bool)$nodeReferenceAll;

        if (!$this->nodeReferenceAll) {
            if (null === $nodeReferenceWhitelist) {
                $nodeReferenceWhitelist = variable_get('ucms_site_node_reference_whitelist', []);
            }
            $this->nodeReferenceWhitelist = $nodeReferenceWhitelist;
        }
    }

    public function onPrepare(NodeEvent $event)
    {
        $node = $event->getNode();

        if (!$this->nodeManager->getAccessService()->typeIsClonable($node->getType())) {
            $node->is_clonable = 0;
        }

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
            // Sites the node is really attached to
            $node->ucms_sites = [];
            // Sites the node is attached to and the current user can see
            $node->ucms_allowed_sites = [];
            // Sites the node is attached to and are enabled
            $node->ucms_enabled_sites = [];
        }

        foreach ($r as $row) {
            $node = $nodes[$row->nid];
            $node->ucms_sites[] = $row->site_id;
        }

        // Repeat it with user visible sites allowing administration pages to
        // compute visible node links for the user, that's why we restrict this
        // extra query to master site only
        if (!$this->manager->hasContext()) {

            $q = $this->db->select('ucms_site_node', 'usn')->fields('usn', ['nid', 'site_id']);
            $q->join('ucms_site', 'us', 'us.id = usn.site_id');
            $r = $q
                ->fields('us', ['state'])
                ->condition('usn.nid', array_keys($nodes))
                ->orderBy('usn.nid')
                ->addTag('ucms_site_access')
                ->execute()
            ;
            foreach ($r as $row) {
                $node = $nodes[$row->nid];
                $node->ucms_allowed_sites[] = $row->site_id;
                if (SiteState::ON === (int)$row->state) {
                    $node->ucms_enabled_sites[] = $row->site_id;
                }
            }
        } else {
            // Counterpart whenever there is a site context, if node is visible
            // whithin this context (user can see the node) then the most
            // revelant site IS the current context; in this very specific use
            // case we do believe that node access stuff has already run prior
            // to us, so we don't care about it
            $context = $this->manager->getContext();
            $siteId = $context->getId();
            $enabled = SiteState::ON === $context->getState();
            foreach ($nodes as $node) {
                if (in_array($siteId, $node->ucms_sites)) {
                    $node->ucms_allowed_sites[] = $siteId;
                    if ($enabled) {
                        $node->ucms_enabled_sites[] = $siteId;
                    }
                }
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

        // Do not continue if we are creating a global node from a local one.
        if ($node->is_global == 1 && !empty($node->parent_nid)) {
            return;
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
            $node->site_id = null;
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
            if (!$site->hasHome()) {
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

        $event = new NodeReferenceCollectEvent($node);
        $this->eventDispatcher->dispatch(NodeReferenceCollectEvent::EVENT_NAME, $event);

        if ($node->ucms_sites) {
            $references = [];
            $references[] = $node->id();

            foreach ($event->getReferences() as $reference) {
                if (NodeReference::TYPE_MEDIA !== $reference->getType()) {
                    $references[] = $reference->getTargetId();
                }
            }

            if ($references) {
                foreach ($node->ucms_sites as $siteId) {
                    $this->nodeManager->createReferenceBulkInSite($siteId, $references);
                }
            }
        }
    }

    public function onDelete(NodeEvent $event)
    {
        // We do not need to delete from the {ucms_site_node} table since it's
        // being done by ON DELETE CASCADE deferred constraints
    }

    public function onNodeReferenceCollect(NodeReferenceCollectEvent $event)
    {
        //
        // Ensure that node-referenced nodes will be attached to site as well.
        // This will fix the following behavior:
        //
        // (sorry in french, I will translate later):
        //
        // 1. l'utilisateur créé des contenus A et B dans le site 1
        // 2. il créé un contenu C dans le site 2
        // 3. il créé ensuite un contenu D global
        // 4. il créé un contenu dans le site 1 et met en node référence les contenus
        //    A, B, C et D
        //     => Première erreur: les contenus A et B sont déjà dans le site 1, mais C
        //        et D ne sont pas référencé à ce moment là dans le site 1
        // 5. il clone le site 1 en site 3
        //     => A et B étant déjà dans le site 1, leurs références sont bien mises
        //        à jour et reportées dans le site 3
        //     => Deuxième erreur: les contenus C et D eux, n'ayant pas de référence
        //        explicite dans le site 1, n'en ont pas non plus dans le site 3
        //
        // Basically, if a node is referenced by a field of another node, it
        // will be seamlessly attached to site when we save the node that
        // carries the reference.
        //
        // Because not all reference fields need the node to be referenced, it
        // will be done by manually whitelisting fields (default is all nodes
        // are referenced, except for already existing installations, since that
        // this is a behavioral change).
        //

        $node = $event->getNode();

        if (!$this->nodeReferenceWhitelist && !$this->nodeReferenceAll || empty($node->ucms_sites)) {
            return; // Feature is completely disabled
        }

        foreach (field_info_instances('node', $node->bundle()) as $fieldname => $instance) {

            // Honnor field whitelist if set
            if (!$this->nodeReferenceAll) {
                if (!in_array($fieldname, $this->nodeReferenceWhitelist)) {
                    continue;
                }
            }

            $field = field_info_field($fieldname);
            switch ($field['type']) {

                case 'ulink':
                    if ($items = field_get_items('node', $node, $fieldname)) {
                        foreach ($items as $item) {
                            $matches = [];
                            if (preg_match('#^(entity:|)node(:|/)(\d+)$#', $item['value'], $matches)) {
                                $event->addReferences(NodeReference::TYPE_FIELD, [$matches[3]], $fieldname);
                            }
                        }
                    }
                    break;

                case 'unoderef':
                    if ($items = field_get_items('node', $node, $fieldname)) {
                        foreach ($items as $item) {
                            $event->addReferences(NodeReference::TYPE_FIELD, [$item['nid']], $fieldname);
                        }
                    }
                    break;

                default:
                    break; // Nothing to do
            }
        }
    }

    /**
     * @todo move this somewhere else, maybe generic in 'sf_dic' module
     */
    public function onNodeAccessChange(ResourceEvent $event)
    {
        // Rebuild node access rights
        $nodes = $this
            ->entityManager
            ->getStorage('node')
            ->loadMultiple($event->getResourceIdList())
        ;

        foreach ($nodes as $node) {
            node_access_acquire_grants($node);
        }
    }
}
