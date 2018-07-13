<?php

namespace MakinaCorpus\Ucms\Contrib;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Ucms\Dashboard\Page\LinksFilterDisplay;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Dashboard\Page\SortManager;
use MakinaCorpus\Ucms\Site\EventDispatcher\NodeReference;

class ReferenceDatasource extends AbstractDatasource
{
    use StringTranslationTrait;

    private $database;
    private $entityManager;

    /**
     * Default constructor
     */
    public function __construct(EntityManager $entityManager, \DatabaseConnection $database)
    {
        $this->database = $database;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters($query)
    {
        return [
            (new LinksFilterDisplay('dead', $this->t("Filter")))
                ->setChoicesMap(['1' => t("Dead links")]),
            (new LinksFilterDisplay('type', $this->t("Type")))
                ->setChoicesMap([
                    NodeReference::TYPE_MEDIA => t("Media"),
                    NodeReference::TYPE_LINK => t("Link in text"),
                    NodeReference::TYPE_FIELD => t("Reference field"),
                    NodeReference::TYPE_UNKNOWN => t("Others"),
                ]),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSortFields($query)
    {
        return [
            't.ts_touched'  => $this->t("update date"),
            't.type'        => $this->t("type"),
            't.field_name'  => $this->t("field name"),
            'n.title'       => $this->t("node title"),
            's.title'       => $this->t("target title")
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSort()
    {
        return ['t.ts_touched', SortManager::DESC];
    }

    private function preloadEverything(array $items)
    {
        // Preload users
        $userIdMap = [];

        // Preload nodes
        $nodeIdMap = [];

        /** @var \MakinaCorpus\Ucms\Site\EventDispatcher\NodeReference $item */
        foreach ($items as $item) {
            if ($userId = $item->getSourceUserId()) {
                $userIdMap[$userId] = $userId;
            }
            if ($nodeId = $item->getSourceId()) {
                $nodeIdMap[$nodeId] = $nodeId;
            }
            if ($item->targetExists() && ($targetId = $item->getTargetId())) {
                $nodeIdMap[$targetId] = $targetId;
            }
        }

        if ($userIdMap) {
            $this->entityManager->getStorage('user')->loadMultiple($userIdMap);
        }

        if ($nodeIdMap) {
            $this->entityManager->getStorage('node')->loadMultiple($nodeIdMap);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getItems($query, PageState $pageState)
    {
        $select = $this->database->select('ucms_node_reference', 't');

        // Add join to node only for node_access, necessary
        $select->join('node', 'n', "n.nid = t.source_id");
        $select->addTag('node_access');
        $select->addMetaData('op', 'update');

        // And really, I am sorry Yannick, but in the end I have no choice,
        // we need this join to ensure the node exists or not, it could have
        // been a sub-request in select, but MySQL does not allow this.
        // IMPORTANT NOTE: we use 'node_revision' here otherwise the
        // 'node_access' tag will also match this table and create false
        // negatives, wrongly hiding nodes from the 'node n' table.
        $select->leftJoin('node_revision', 's', "s.nid = t.target_id");
        $select->fields('t', ['source_id', 'target_id', 'type', 'field_name', 'ts_touched']);
        $select->addField('n', 'title', 'source_title');
        $select->addField('n', 'uid', 'source_user_id');
        $select->addField('n', 'changed', 'ts_source');
        $select->addField('s', 'title', 'target_title');
        $select->addExpression('s.nid', 'target_exists');

        if (!empty($query['dead'])) {
            $select->condition((new \DatabaseCondition('OR'))
                ->condition('s.status', 0)
                ->isNull('s.nid')
            );
        }

        if (isset($query['type'])) {
            $select->condition('t.type', $query['type']);
        }

        if ($pageState->hasSortField()) {
            $select->orderBy(
                $pageState->getSortField(),
                $pageState->getSortOrder() === PageState::SORT_ASC ? 'asc' : 'desc'
            );
        }
        $select->orderBy('n.nid', 'desc');

        // Pager handling
        $total = $select->countQuery()->execute()->fetchField();
        $pageState->setTotalItemCount($total);

        $ret = $select
            ->range($pageState->getOffset(), $pageState->getLimit())
            ->execute()
            ->fetchAll(\PDO::FETCH_CLASS, NodeReference::class)
        ;

        if ($ret) {
            $this->preloadEverything($ret);
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function hasSearchForm()
    {
        return false;
    }
}
