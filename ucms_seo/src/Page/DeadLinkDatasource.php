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
            ->fields('t')
            ->notExists($subquery);

        $result = $query->execute()->fetchAll();

        $nids = [];
        foreach ($result as $row) {
            $nids[] = $row->source_nid;
            $nids[] = $row->destination_nid;
        }
        $nodes = $this
            ->entityManager
            ->getStorage('node')
            ->loadMultiple($nids)
        ;

        $ret = [];
        foreach ($result as $row) {
            $ret[] = [
                'source' => isset($nodes[$row->source_nid]) ? $nodes[$row->source_nid] : null,
                'source_field' => $row->source_field,
                'destination_nid' => $row->destination_nid,
                'destination_url' => $row->destination_url,
                'destination_deleted' => !isset($nodes[$row->destination_nid]),
            ];
        }
        return $ret;
    }
}
