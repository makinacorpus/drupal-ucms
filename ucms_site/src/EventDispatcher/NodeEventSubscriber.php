<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
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

    private $database;
    private $manager;
    private $nodeManager;
    private $entityManager;
    private $eventDispatcher;
    private $nodeReferenceAll;
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
            NodeAccessChangeEvent::EVENT_NAME => [
                ['onNodeAccessChange', 0],
            ],
            NodeReferenceCollectEvent::EVENT_NAME => [
                ['onNodeReferenceCollect', 0],
                ['onNodeReferenceCollectPersist', -2048],
            ],
        ];
    }

    /**
     * Constructor
     */
    public function __construct(
        Connection $database,
        SiteManager $manager,
        NodeManager $nodeManager,
        EntityManager $entityManager,
        EventDispatcherInterface $eventDispatcher,
        bool $nodeReferenceAll = true,
        array $nodeReferenceWhitelist = []
    ) {
        $this->database = $database;
        $this->manager = $manager;
        $this->nodeManager = $nodeManager;
        $this->entityManager = $entityManager;
        $this->eventDispatcher= $eventDispatcher;
        $this->nodeReferenceAll = (bool)$nodeReferenceAll;
        if (!$this->nodeReferenceAll) {
            $this->nodeReferenceWhitelist = [];
        }
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
            $node->set('ucms_sites', []);
        }
    }

    public function onLoad(NodeCollectionEvent $event)
    {
        /** @var \Drupal\node\NodeInterface $nodes */
        $nodes = $event->getNodes();

        // Attach site identifiers list to each node being loaded. Althought it does
        // and extra SQL query, this being the core of the whole site business, we
        // can't bypass this step nor risk it being stalled with unsynchronized data
        // in cache.
        $map = [];
        $allowed = [];
        $enabled = [];

        // @todo Later in the future, determiner an efficient way of caching it,
        // we'll need this data to be set in Elastic Search anyway so we'll risk data
        // stalling in there.
        $r = $this
            ->database
            ->select('ucms_site_node', 'usn')
            ->fields('usn', ['nid', 'site_id'])
            ->condition('usn.nid', array_keys($nodes), 'IN')
            ->orderBy('usn.nid')
            ->execute()
        ;

        foreach ($r as $row) {
            $map[$row->nid][] = (int)$row->site_id;
        }

        // Repeat it with user visible sites allowing administration pages to
        // compute visible node links for the user, that's why we restrict this
        // extra query to master site only
        if (!$this->manager->hasContext()) {

            $q = $this->database->select('ucms_site_node', 'usn')->fields('usn', ['nid', 'site_id']);
            $q->join('ucms_site', 'us', 'us.id = usn.site_id');
            $r = $q
                ->fields('us', ['state'])
                ->condition('usn.nid', array_keys($nodes), 'IN')
                ->orderBy('usn.nid')
                ->addTag('ucms_site_access')
                ->execute()
            ;
            foreach ($r as $row) {
                $allowed[$row->nid][] = (int)$row->site_id;
                if (SiteState::ON === (int)$row->state) {
                    $enabled[$row->nid][] = (int)$row->site_id;
                }
            }
        } else {
            // Counterpart whenever there is a site context, if node is visible
            // whithin this context (user can see the node) then the most
            // revelant site IS the current context; in this very specific use
            // case we do believe that node access stuff has already run prior
            // to us, so we don't care about it
            $context = $this->manager->getContext();
            $isEnabled = SiteState::ON === $context->getState();
            $siteId = $context->getId();

            foreach ($nodes as $node) {
                $nodeId = $node->id();
                if (in_array($siteId, $map[$nodeId] ?? [])) {
                    $allowed[$nodeId][] = $siteId;
                    if ($isEnabled) {
                        $enabled[$nodeId][] = $siteId;
                    }
                }
            }
        }

        // Populate nodes last. We can't do it along the way upper because
        // when we attempt nodes property write using magic accessors, it
        // will not work because arrays get copied instead of referenced.
        foreach ($nodes as $node) {
            $nodeId = $node->id();
            $node->set('ucms_sites', $map[$nodeId] ?? []);
            $node->set('ucms_allowed_sites', $allowed[$nodeId] ?? []);
            $node->set('ucms_enabled_sites', $enabled[$nodeId] ?? []);
        }
    }

    public function onPreInsert(NodeEvent $event)
    {
        $node = $event->getNode();

        $isGlobal = (bool)$node->get('is_global')->value;
        $parentNodeId = (int)$node->get('parent_nid')->value;
        $nodeSiteId = (int)$node->get('site_id')->value;

        // Do not continue if we are creating a global node from a local one.
        if ($isGlobal && $parentNodeId) {
            return;
        }

        // If the node is created in a specific site context, then gives the
        // node ownership to this site, note this might change in the end, it
        // is mostly the node original site.
        if ($nodeSiteId) {
            $node->set('ucms_sites', [$nodeSiteId]);
            $node->set('is_global', false);
        } else if (false !== $nodeSiteId) {
            // @todo check what does this false mean...
            if ($site = $this->manager->getContext()) {
                $siteId = $site->getId();
                $node->set('site_id', $siteId);
                // When a node is cloned, site list already exist from the
                // parent node. Drop all other sites and set this one.
                $node->set('ucms_sites', [$siteId]);
                $node->set('is_global', false);
            } else {
                $node->set('is_global', true);
            }
        } else {
            $node->set('is_global', true);
        }
    }

    public function onInsert(NodeEvent $event)
    {
        $node = $event->getNode();
        $nodeSiteId = $node->get('site_id')->value;
        $site = $nodeSiteId ? $this->manager->getStorage()->findOne($nodeSiteId) : null;

        if ($site) {
            $this->nodeManager->createReference($site, $node);

            // Site might not have an homepage, because the factory wasn't
            // configured properly before creation, so just set this node
            // as home page.
            if (!$site->hasHome()) {
                $site->home_nid = $node->id();
                $this->manager->getStorage()->save($site, ['home_nid']);
            }
        }

        if ($event->isClone() && $site) {
            $storage = $this->manager->getStorage();
            $site = $storage->findOne($nodeSiteId);

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

    /*
     * @todo Investigate this - a clone should not have been referenced to parent site's
     *
    public function onPostInsert(NodeEvent $event)
    {
        $node = $event->getNode();

        // This MUST happen after the layouts are being changed, else the
        // foreign key cascade constraints will happen and nothing will be
        // duplicated
        if ($event->isClone() && $node->  si  te_id && $node->  pare  nt_nid) {
            $siteId = (int)$node->get('site_id')->value();
            $parentNid = (int)$node->get():
            // Dereference node from the clone site, since it will be replaced
            // by the new one in all contextes it can be
            $this->nodeManager->deleteReferenceBulkFromSite($node->site_id, [$node->parent_nid]);
        }
    }
     */

    public function onSave(NodeEvent $event)
    {
        $node = $event->getNode();

        $event = new NodeReferenceCollectEvent($node);
        $this->eventDispatcher->dispatch(NodeReferenceCollectEvent::EVENT_NAME, $event);

        if (!($items = $node->get('ucms_sites'))->isEmpty()) {
            $references = [];
            $references[] = $node->id();

            foreach ($event->getReferences() as $reference) {
                if (NodeReference::TYPE_MEDIA !== $reference->getType()) {
                    $references[] = $reference->getTargetId();
                }
            }

            if ($references) {
                foreach ($items->getValue() as $item) {
                    $this->nodeManager->createReferenceBulkInSite((int)$item['value'], $references);
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

        if ((!$this->nodeReferenceWhitelist && !$this->nodeReferenceAll) || $node->get('ucms_sites')->isEmpty()) {
            return; // Feature is completely disabled
        }

        foreach ($node->getFields() as $fieldname => $items) {

            // Honnor field whitelist if set, also don't process empty fields.
            if ($items->isEmpty() || (!$this->nodeReferenceAll && !\in_array($fieldname, $this->nodeReferenceWhitelist))) {
                continue;
            }

            switch ($items->getFieldDefinition()->getType()) {

                case 'text':
                case 'text_long':
                case 'text_with_summary':
                    foreach ($items as $item) {
                        $matches = [];
                        if (\preg_match('#(entity:|)node(:|/)(\d+)#', $item->value, $matches)) {
                            $event->addReferences(NodeReference::TYPE_FIELD, [$matches[3]], $fieldname);
                        }
                    }
                    break;

                case 'ulink':
                    foreach ($items as $item) {
                        $matches = [];
                        if (\preg_match('#^(entity:|)node(:|/)(\d+)$#', $item->value, $matches)) {
                            $event->addReferences(NodeReference::TYPE_FIELD, [$matches[3]], $fieldname);
                        }
                    }
                    break;

                case 'unoderef':
                    foreach ($items as $item) {
                        $event->addReferences(NodeReference::TYPE_FIELD, [$item->nid], $fieldname);
                    }
                    break;

                default:
                    break; // Nothing to do
            }
        }
    }

    public function onNodeReferenceCollectPersist(NodeReferenceCollectEvent $event)
    {
        $node = $event->getNode();

        // This will happen anyway, references are going to be rebuilt each save.
        $this->database->delete('ucms_node_reference')->condition('source_id', $node->id())->execute();

        if ($references = $event->getReferences()) {
            // Proceed only if references are found.
            $query = $this->database->insert('ucms_node_reference')->fields(['source_id', 'target_id', 'type', 'field_name', 'ts_touched']);
            $now = (new \DateTime())->format('Y-m-d H:i:s');

            foreach ($references as $reference) {
                $query->values([
                    $reference->getSourceId(),
                    $reference->getTargetId(),
                    $reference->getType(),
                    $reference->getFieldName(),
                    $now
                ]);
            }

            $query->execute();
        }
    }

    /**
     * @todo move this somewhere else, maybe generic in 'sf_dic' module
     */
    public function onNodeAccessChange(NodeAccessChangeEvent $event)
    {
        // Rebuild node access rights
        $nodes = $this->entityManager->getStorage('node')->loadMultiple($event->getNodeIdList());

        $accessControlHandler = $this->entityManager->getAccessControlHandler('node');

        foreach ($nodes as $node) {
            $grants = $accessControlHandler->acquireGrants($node);
            \Drupal::service('node.grant_storage')->write($node, $grants, null, true);
        }
    }
}
