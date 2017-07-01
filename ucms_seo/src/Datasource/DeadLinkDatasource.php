<?php

namespace MakinaCorpus\Ucms\Seo\Datasource;

use Drupal\Core\Entity\EntityManager;
use MakinaCorpus\Calista\Datasource\AbstractDatasource;
use MakinaCorpus\Calista\Datasource\Query;
use MakinaCorpus\Drupal\Calista\Datasource\QueryExtender\DrupalPager;
use MakinaCorpus\Ucms\Contrib\NodeReference;

class DeadLinkDatasource extends AbstractDatasource
{
    private $database;
    private $entityManager;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $database
     * @param EntityManager $entityManager
     */
    public function __construct(\DatabaseConnection $database, EntityManager $entityManager)
    {
        $this->database = $database;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemClass()
    {
        return NodeReference::class;
    }

    /**
     * Node reference is dead if it is :
     * - deleted
     * - unpublished
     */
    public function getItems(Query $query)
    {
        $select = $this->database->select('ucms_node_reference', 't');
        // Add join to node only for node_access, necessary
        $select->join('node', 'n', "n.nid = t.source_id");
        $select->addTag('node_access');
        // And really, I am sorry Yannick, but in the end I have no choice,
        // we need this join to ensure the node exists or not, it could have
        // been a sub-request in select, but MySQL does not allow this
        $select->leftJoin('node', 's', "s.nid = t.target_id");
        $select->condition((new \DatabaseCondition('OR'))
            ->condition('s.status', 0)
            ->isNull('s.nid')
        );
        $select->fields('t', ['source_id', 'target_id', 'type', 'field_name']);
        $select->addField('n', 'title', 'source_title');
        $select->addField('n', 'type', 'source_bundle');
        $select->addField('s', 'title', 'target_title');
        $select->addExpression('s.nid', 'target_exists');

        /** @var \MakinaCorpus\Drupal\Calista\Datasource\QueryExtender\DrupalPager $pager */
        $pager = $select->extend(DrupalPager::class)->setDatasourceQuery($query);
        $ret = $pager->execute()->fetchAll(\PDO::FETCH_CLASS, NodeReference::class);

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

        return $this->createResult($ret, $pager->getTotalCount());
    }
}
