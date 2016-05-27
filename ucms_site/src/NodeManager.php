<?php

namespace MakinaCorpus\Ucms\Site;

use Drupal\Core\Entity\EntityManager;
use Drupal\node\NodeInterface;

use MakinaCorpus\Ucms\Site\EventDispatcher\SiteAttachEvent;

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
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param SiteManager $manager
     * @param EntityManager $entityManager
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(\DatabaseConnection $db, SiteManager $manager, EntityManager $entityManager, EventDispatcher $eventDispatcher)
    {
        $this->db = $db;
        $this->manager = $manager;
        $this->entityManager = $entityManager;
        $this->eventDispatcher= $eventDispatcher;
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

        $this->eventDispatcher->dispatch(SiteAttachEvent::EVENT_ATTACH, new SiteAttachEvent($site, [$node->id()]));
    }

    /**
     * Reference node for a site
     *
     * @param Site $site
     * @param int[] $nodeIdList
     */
    public function createReferenceBulk(Site $site, $nodeIdList)
    {
        // @todo Optimize me
        foreach ($nodeIdList as $id) {
            $this
                ->db
                ->merge('ucms_site_node')
                ->key(['nid' => $id, 'site_id' => $site->id])
                ->execute()
            ;
        }

        $this
            ->entityManager
            ->getStorage('node')
            ->resetCache($nodeIdList)
        ;

        $this->eventDispatcher->dispatch(SiteAttachEvent::EVENT_ATTACH, new SiteAttachEvent($site, $nodeIdList));
    }

    /**
     * Unreference node for a site
     *
     * @param Site $site
     * @param int[] $nodeIdList
     */
    public function deleteReferenceBulk(Site $site, $nodeIdList)
    {
        $this
            ->db
            ->delete('ucms_site_node')
            ->condition('nid', $nodeIdList)
            ->condition('site_id', $site->id)
            ->execute()
        ;

        $this
            ->entityManager
            ->getStorage('node')
            ->resetCache($nodeIdList)
        ;

        $this->eventDispatcher->dispatch(SiteAttachEvent::EVENT_DETACH, new SiteAttachEvent($site, $nodeIdList));
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
