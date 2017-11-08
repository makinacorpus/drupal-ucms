<?php

namespace MakinaCorpus\Ucms\Seo\Page;

use Drupal\Core\Entity\EntityManager;

use MakinaCorpus\Ucms\Contrib\NodeReference;
use MakinaCorpus\Ucms\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;

class DeadLinkDatasource extends AbstractDatasource
{
    private $db;
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
        $query = $this->db->select('ucms_node_reference', 't');

        // Add join to node only for node_access, necessary
        $query->join('node', 'n', "n.nid = t.source_id");
        $query->addTag('node_access');
        $query->addMetaData('op', 'update');

        // And really, I am sorry Yannick, but in the end I have no choice,
        // we need this join to ensure the node exists or not, it could have
        // been a sub-request in select, but MySQL does not allow this.
        // IMPORTANT NOTE: we use 'node_revision' here otherwise the
        // 'node_access' tag will also match this table and create false
        // negatives, wrongly hiding nodes from the 'node n' table.
        $query->leftJoin('node_revision', 's', "s.nid = t.target_id");
        $query->condition((new \DatabaseCondition('OR'))
            ->condition('s.status', 0)
            ->isNull('s.nid')
        );
        $query->fields('t', ['source_id', 'target_id', 'type', 'field_name']);
        $query->addExpression('s.nid', 'target_exists');

        $ret = $query->execute()->fetchAll(\PDO::FETCH_CLASS, NodeReference::class);

        // Preload everything since it's for displaying just later.
        $nids = [];
        foreach ($ret as $reference) {
            /** @var $reference NodeReference */
            // Source always exists, since there is a foreign key constraint.
            $nids[] = $reference->getSourceId();
            if ($reference->targetExists()) {
                $nids[] = $reference->getTargetId();
            }
        }
        $this->entityManager->getStorage('node')->loadMultiple($nids);

        return $ret;
    }
}
