<?php

namespace MakinaCorpus\Ucms\Group\Datasource;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Calista\Datasource\AbstractDatasource;
use MakinaCorpus\Calista\Datasource\Filter;
use MakinaCorpus\Calista\Datasource\Query;
use MakinaCorpus\Drupal\Calista\Datasource\QueryExtender\DrupalPager;
use MakinaCorpus\Ucms\Site\Group;
use MakinaCorpus\Ucms\Site\GroupManager;

class GroupAdminDatasource extends AbstractDatasource
{
    use StringTranslationTrait;

    private $database;
    private $groupManager;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param GroupManager $groupManager
     */
    public function __construct(\DatabaseConnection $database, GroupManager $groupManager)
    {
        $this->database = $database;
        $this->groupManager = $groupManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new Filter('site'),
            new Filter('uid'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSorts()
    {
        return [
            'g.id'          => $this->t("identifier"),
            'g.title'       => $this->t("title"),
            'g.ts_changed'  => $this->t("lastest update date"),
            'g.ts_created'  => $this->t("creation date"),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(Query $query)
    {
        $q = $this
            ->database
            ->select('ucms_group', 'g')
            ->fields('g', ['id'])
            ->addTag('ucms_group_access')
            ->groupBy('g.id')
        ;

        if ($query->has('uid')) {
            $q->join('ucms_group_access', 'gu', "gu.group_id = g.id");
            $q->condition('gu.user_id', $query->get('uid'));
        }
        if ($query->has('site')) {
            $q->join('ucms_site', 'gs', "gs.group_id = g.id");
            $q->condition('gs.id', $query->get('site'));
        }

        if ($query->hasSortField()) {
            $q->orderBy($query->getSortField(), $query->getSortOrder());
        }
        $q->orderBy('g.id', $query->getSortOrder());

        $search = $query->getSearchString();
        if ($search) {
            $q->condition('g.title', '%' . db_like($search) . '%', 'LIKE');
        }

        /** @var \MakinaCorpus\Drupal\Calista\Datasource\QueryExtender\DrupalPager $pager */
        $pager = $q->extend(DrupalPager::class)->setDatasourceQuery($query);
        $idList = $pager->execute()->fetchCol();

        $items = $this->groupManager->loadAll($idList);

        return $this->createResult($items, $pager->getTotalCount());
    }

    /**
     * {@inheritdoc}
     */
    public function getItemClass()
    {
        return Group::class;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsFulltextSearch()
    {
        return true;
    }
 }
