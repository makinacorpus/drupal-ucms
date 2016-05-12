<?php


namespace MakinaCorpus\Ucms\Seo\Page;


use Drupal\Core\Entity\EntityManager;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;


class DeadLinkDatasource extends AbstractDatasource
{

    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param EntityManager $entityManager
     */
    public function __construct(\DatabaseConnection $db, EntityManager $entityManager)
    {
        $this->db = $db;
        $this->entityManager = $entityManager;
    }

    /**
     * Node reference is dead if it is :
     * - deleted
     * - unpublished
     */
    public function getItems($query, PageState $pageState)
    {
        $subquery = $this->db->select('node', 'n')
            ->fields('n', ['nid'])
            ->condition('n.status', 1)
            ->where('t.destination_nid = n.nid');

        $query = $this->db->select('ucms_seo_deadlinks_tracking', 't')
            ->fields('t', ['source_nid', 'destination_nid'])
            ->notExists($subquery);

        $result = $query->execute()->fetchAllKeyed();
        $nodes = $this
            ->entityManager
            ->getStorage('node')
            ->loadMultiple(array_keys($result))
        ;

        $ret = [];
        foreach ($result as $source_nid => $destination_nid) {
            $ret[] = [
                'source' => isset($nodes[$source_nid]) ? $nodes[$source_nid] : null,
                'destination_nid' => $destination_nid,
            ];
        }
        return $ret;
    }
}
