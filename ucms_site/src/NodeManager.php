<?php

namespace MakinaCorpus\Ucms\Site;

use Drupal\Core\Entity\EntityManager;
use Drupal\node\NodeInterface;

use MakinaCorpus\Ucms\Site\EventDispatcher\SiteAttachEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvents;

use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Handles whatever needs to be done with nodes
 *
 * @todo unit test
 */
class NodeManager
{
    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * @var SiteManager
     */
    private $manager;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var NodeAccessService
     */
    private $nodeAccessService;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param SiteManager $manager
     * @param EntityManager $entityManager
     * @param NodeAccessService $nodeAccessService
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(
        \DatabaseConnection $db,
        SiteManager $manager,
        EntityManager $entityManager,
        NodeAccessService $nodeAccessService,
        EventDispatcher $eventDispatcher
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
        $this
            ->db
            ->merge('ucms_site_node')
            ->key(['nid' => $node->id(), 'site_id' => $site->id])
            ->execute()
        ;

        if (!in_array($site->id, $node->ucms_sites)) {
            $node->ucms_sites[] = $site->id;
        }

        $this->eventDispatcher->dispatch(SiteEvents::EVENT_ATTACH, new SiteAttachEvent($site->getId(), $node->id()));
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
        // @todo Optimize me
        foreach ($nodeIdList as $nodeId) {
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
            ->resetCache($nodeIdList)
        ;

        $this->eventDispatcher->dispatch(SiteEvents::EVENT_ATTACH, new SiteAttachEvent($siteId, $nodeIdList));
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

        // Fetcth the new user identifier from the updates
        $userId = isset($updates['uid']) ? $updates['uid'] : $original->getOwnerId();

        $node->nid      = null;
        $node->vid      = null;
        $node->tnid     = null;
        $node->log      = null;
        $node->uid      = $userId;
        $node->created  = null;
        $node->changed  = null;
        $node->path     = null;
        $node->files    = [];
        // Fills in the some default values
        $node->status   = 0;
        $node->promote  = 0;
        $node->sticky   = 0;
        $node->revision = 1;
        // Resets sites information
        $node->site_id  = null;
        $node->ucms_sites = [];
        // Sets the origin_id and parent_id
        $node->parent_nid = $original->id();
        $node->origin_nid = empty($original->origin_nid) ? $original->id() : $original->origin_nid;
        // Forces node indexing
        $node->ucms_index_now = 1; // @todo find a better way

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
            SELECT sa.site_id
            FROM ucms_site_access sa
            WHERE
                sa.uid = 13 -- current user
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
}
