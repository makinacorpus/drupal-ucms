<?php

namespace MakinaCorpus\Ucms\Site;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManager;
use Drupal\node\NodeInterface;
use MakinaCorpus\Ucms\Site\EventDispatcher\NodeEvents;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteAttachEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Handles whatever needs to be done with nodes
 *
 * @todo unit test
 */
class NodeManager
{
    private $db;
    private $manager;
    private $entityManager;
    private $nodeAccessService;
    private $eventDispatcher;
    private $cloningMapping = [];

    /**
     * Default constructor
     */
    public function __construct(
        Connection $db,
        SiteManager $manager,
        EntityManager $entityManager,
        NodeAccessService $nodeAccessService,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->db = $db;
        $this->manager = $manager;
        $this->entityManager = $entityManager;
        $this->nodeAccessService = $nodeAccessService;
        $this->eventDispatcher= $eventDispatcher;
    }

    /**
     * Get node access service
     *
     * @return NodeAccessService
     */
    public function getAccessService()
    {
        return $this->nodeAccessService;
    }

    /**
     * Reference node into a site
     *
     * @param Site $site
     * @param NodeInterface $node
     */
    public function createReference(Site $site, NodeInterface $node)
    {
        $nodeId = $node->id();
        $siteId = $site->getId();

        $this
            ->db
            ->merge('ucms_site_node')
            ->key(['nid' => $nodeId, 'site_id' => $siteId])
            ->execute()
        ;

        if (!in_array($siteId, $node->ucms_sites)) {
            $node->ucms_sites[] = $siteId;
        }

        // FIXME: is that useful?
        //$this->eventDispatcher->dispatch(NodeEvents::ACCESS_CHANGE, new ResourceEvent('node', [$nodeId]));
        $this->eventDispatcher->dispatch(SiteEvents::EVENT_ATTACH, new SiteAttachEvent($siteId, $nodeId));
    }

    /**
     * Reference a single within multiple sites
     *
     * @param int $nodeId
     * @param int[] $siteIdList
     */
    public function createReferenceBulkForNode($nodeId, $siteIdList)
    {
        // @todo Optimize me
        foreach ($siteIdList as $siteId) {
            $this
                ->db
                ->merge('ucms_site_node')
                ->key(['nid' => $nodeId, 'site_id' => $siteId])
                ->execute()
            ;
        }

        $this
            ->entityManager
            ->getStorage('node')
            ->resetCache([$nodeId])
        ;

        $this->eventDispatcher->dispatch(NodeEvents::ACCESS_CHANGE, new ResourceEvent('node', [$nodeId]));
        $this->eventDispatcher->dispatch(SiteEvents::EVENT_ATTACH, new SiteAttachEvent($siteIdList, $nodeId));
    }

    /**
     * Reference multiple nodes within a single site
     *
     * @param int $siteId
     * @param int[] $nodeIdList
     */
    public function createReferenceBulkInSite($siteId, $nodeIdList)
    {
        //
        // With PostgreSQL, I would have wrote a single SQL INSERT query that
        // INSERT ... WHERE NOT IN to avoid duplicate, using a RETURNING clause
        // allowing me to fetch directly the missing inserted items, and run
        // the event only onto them. Also, it would be atomic, race would not
        // be possible.
        //
        // But we are also supporting MySQL. I hate MySQL. Hence the 3-step
        // (SELECT, array_diff, INSERT) dirty algorith right under us.
        //
        // It is prone to race conditions because we don't lock the table
        // if 2 concurent requests attempt to insert the same node, they
        // can, if you are unlucky, race altogether, one of them will fail
        // with an exception raised. I hope this won't happen too much,
        // hopefully, no data loss is posible here.
        //

        // Avoid SQL breakage with empty IN() clause in next SELECT query
        if (!$nodeIdList) {
            return;
        }

        // Ensures that nodes exists in the node table
        $nodeIdList = $this
            ->db
            ->select('node', 'n')
            ->fields('n', ['nid'])
            ->condition('nid', $nodeIdList)
            ->execute()
            ->fetchCol()
        ;

        if (!$nodeIdList) {
            return; // Nothing to be done, really
        }

        // Do NOT reinsert already referenced nodes
        $existing = $this
            ->db
            ->select('ucms_site_node', 'usn')
            ->fields('usn', ['nid'])
            ->condition('usn.site_id', $siteId)
            ->condition('usn.nid', $nodeIdList)
            ->execute()
            ->fetchCol()
        ;

        if ($missing = array_diff($nodeIdList, $existing)) {

            $insert = $this->db->insert('ucms_site_node')->fields(['site_id', 'nid']);
            foreach ($missing as $nodeId) {
                $insert->values([$siteId, $nodeId]);
            }
            $insert->execute();

            $this->entityManager->getStorage('node')->resetCache($missing);

            // FIXME: is that useful?
            //$this->eventDispatcher->dispatch(NodeEvents::ACCESS_CHANGE, new ResourceEvent('node', $missing));
            $this->eventDispatcher->dispatch(SiteEvents::EVENT_ATTACH, new SiteAttachEvent($siteId, $missing));
        }
    }

    /**
     * Create unsaved node clone
     *
     * @param NodeInterface $original
     *   Original node to clone
     * @param array $updates
     *   Any fields that will replace properties on the new node object, set
     *   the 'uid' property as user identifier
     *
     * @return NodeInterface
     *   Unsaved clone
     */
    public function createUnsavedClone(NodeInterface $original, array $updates = [])
    {
        // This method, instead of the clone operator, will actually drop all
        // existing references and pointers and give you raw values.
        // All credits to https://stackoverflow.com/a/10831885/5826569
        $node = unserialize(serialize($original));

        $node->nid      = null;
        $node->vid      = null;
        $node->tnid     = null;
        $node->log      = null;
        $node->created  = null;
        $node->changed  = null;
        $node->path     = null;
        $node->files    = [];
        // Fills in the some default values.
        $node->status   = 0;
        $node->promote  = 0;
        $node->sticky   = 0;
        $node->revision = 1;
        // Resets sites information.
        $node->site_id  = null;
        $node->ucms_sites = [];
        // Sets the origin_id and parent_id.
        $node->parent_nid = $original->id();
        $node->origin_nid = empty($original->origin_nid) ? $original->id() : $original->origin_nid;
        // Forces node indexing.
        $node->ucms_index_now = 1; // @todo find a better way

        // Sets the node's owner.
        if (isset($updates['uid'])) {
            $account = $this->entityManager->getStorage('user')->load($updates['uid']);
            $node->uid = $account->id();
            $node->name = $account->getAccountName();
            $node->revision_uid = $account->id();
            unset($updates['uid']);
        }

        foreach ($updates as $key => $value) {
            $node->{$key} = $value;
        }

        return $node;
    }

    /**
     * Clone the given node
     *
     * @param NodeInterface $original
     *   Original node to clone
     * @param array $updates
     *   Any fields that will replace properties on the new node object, set
     *   the 'uid' property as user identifier
     *
     * @return NodeInterface
     *   New duplicated node, it has already been saved, to update values use
     *   the $updates parameter
     */
    public function createAndSaveClone(NodeInterface $original, array $updates = [])
    {
        $clone = $this->createUnsavedClone($original, $updates);
        $this->entityManager->getStorage('node')->save($clone);

        return $clone;
    }

    /**
     * Unreference node for a site
     *
     * @param int $siteId
     * @param int[] $nodeIdList
     */
    public function deleteReferenceBulkFromSite($siteId, $nodeIdList)
    {
        if (!$nodeIdList) {
            return;
        }

        $this
            ->db
            ->delete('ucms_site_node')
            ->condition('nid', $nodeIdList)
            ->condition('site_id', $siteId)
            ->execute()
        ;

        $this
            ->entityManager
            ->getStorage('node')
            ->resetCache($nodeIdList)
        ;

        // FIXME: is that useful?
        //$this->eventDispatcher->dispatch(NodeEvents::ACCESS_CHANGE, new ResourceEvent('node', $nodeIdList));
        $this->eventDispatcher->dispatch(SiteEvents::EVENT_DETACH, new SiteAttachEvent($siteId, $nodeIdList));
    }

    /**
     * Find candidate sites for referencing this node
     *
     * @param NodeInterface $node
     * @param int $userId
     *
     * @return Site[]
     */
    public function findSiteCandidatesForReference(NodeInterface $node, $userId)
    {
        $ne = $this
            ->db
            ->select('ucms_site_node', 'sn')
            ->where("sn.site_id = sa.site_id")
            ->condition('sn.nid', $node->id())
        ;
        $ne->addExpression('1');

        $idList = $this
            ->db
            ->select('ucms_site_access', 'sa')
            ->fields('sa', ['site_id'])
            ->condition('sa.uid', $userId)
            ->notExists($ne)
            ->groupBy('sa.site_id')
            ->execute()
            ->fetchCol()
        ;

        return $this->manager->getStorage()->loadAll($idList);
    }

    /**
     * Find candidate sites for cloning this node
     *
     * @param NodeInterface $node
     * @param int $userId
     *
     * @return Site[]
     */
    public function findSiteCandidatesForCloning(NodeInterface $node, $userId)
    {
        /*
         * The right and only working query for this.
         *
            SELECT DISTINCT(sa.site_id)
            FROM ucms_site_access sa
            JOIN ucms_site_node sn ON sn.site_id = sa.site_id
            WHERE
                sa.uid = 13 -- current user
                AND sn.
                AND sa.role = 1 -- webmaster
                AND sa.site_id <> 2 -- node current site
                AND NOT EXISTS (
                    SELECT 1
                    FROM node en
                    WHERE
                        en.site_id = sa.site_id
                        AND (
                            en.parent_nid = 6 -- node we are looking for
                            OR nid = 6
                        )
                )
            ;
          */

        $sq = $this
            ->db
            ->select('node', 'en')
            ->where('en.site_id = sa.site_id')
            ->where('en.parent_nid = :nid1 OR nid = :nid2', [':nid1' => $node->id(), ':nid2' => $node->id()])
        ;

        $sq->addExpression('1');

        $q = $this
            ->db
            ->select('ucms_site_access', 'sa')
            ->fields('sa', ['site_id'])
            ->condition('sa.uid', $userId)
            ->condition('sa.role', Access::ROLE_WEBMASTER)
        ;

        $q->join('ucms_site_node', 'sn', 'sn.site_id = sa.site_id');
        $q->condition('sn.nid', $node->id());

        // The node might not be attached to any site if it is a global content
        if ($node->site_id) {
            $q->condition('sa.site_id', $node->site_id, '<>');
        }

        $idList = $q
            ->notExists($sq)
            ->addTag('ucms_site_access')
            ->execute()
            ->fetchCol()
        ;

        return $this->manager->getStorage()->loadAll($idList);
    }

    /**
     * Provides a mapping array of parent identifiers to clone identifiers.
     *
     * If there is several clones for a same parent,
     * the first created will be passed.
     *
     * @param Site $site
     *
     * @return integer[]
     */
    public function getCloningMapping(Site $site)
    {
        if (isset($this->cloningMapping[$site->getId()])) {
            return $this->cloningMapping[$site->getId()];
        }

        $q = $this->db
            ->select('node', 'n')
            ->fields('n', ['parent_nid', 'nid'])
            ->isNotNull('parent_nid')
            ->condition('site_id', $site->id)
            ->condition('status', NODE_PUBLISHED)
            ->orderBy('created', 'ASC')
        ;

        $mapping = [];

        foreach ($q->execute()->fetchAll() as $row) {
            if (isset($mapping[(int) $row->parent_nid])) {
                continue;
            }
            $mapping[(int) $row->parent_nid] = (int) $row->nid;
        }

        $this->cloningMapping[$site->getId()] = $mapping;

        return $mapping;
    }
}
