<?php

namespace MakinaCorpus\Ucms\Group\Datasource;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Calista\Datasource\AbstractDatasource;
use MakinaCorpus\Calista\Datasource\Filter;
use MakinaCorpus\Calista\Datasource\Query;
use MakinaCorpus\Drupal\Calista\Datasource\QueryExtender\DrupalPager;
use MakinaCorpus\Ucms\Group\GroupManager;
use MakinaCorpus\Ucms\Group\GroupMember;

class GroupMemberAdminDatasource extends AbstractDatasource
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
            new Filter('group'),
            new Filter('uid'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSorts()
    {
        return [
            'gu.user_id'  => $this->t("member identifier"),
            'u.name'      => $this->t("member name"),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(Query $query)
    {
        $q = $this
            ->database
            ->select('ucms_group_user', 'gu')
            ->fields('gu', ['group_id', 'user_id'])
            ->fields('u', ['name', 'mail', 'status'])
            ->addTag('ucms_group_access')
            //->groupBy('gu.user_id')
        ;

        $q->join('users', 'u', "u.uid = gu.user_id");

        if ($query->has('group')) {
            $q->condition('gu.group_id', $query->has('group'));
        }
        if ($query->has('uid')) {
            $q->condition('gu.user_id', $query->get('uid'));
        }

        if ($query->hasSortField()) {
            $q->orderBy($query->getSortField(), $query->getSortOrder());
        }
        $q->orderBy('u.name', $query->getSortOrder());

        $search = $query->getSearchString();
        if ($search) {
            $q->condition(
                (new \DatabaseCondition('OR'))
                    ->condition('u.name', '%' . db_like($search) . '%', 'LIKE')
                    ->condition('u.mail', '%' . db_like($search) . '%', 'LIKE')
            );
        }

        /** @var \MakinaCorpus\Drupal\Calista\Datasource\QueryExtender\DrupalPager $pager */
        $pager = $q->extend(DrupalPager::class)->setDatasourceQuery($query);
        $r = $pager->execute();
        $r->setFetchMode(\PDO::FETCH_CLASS, GroupMember::class);

        $items = $r->fetchAll();

        return $this->createResult($items, $pager->getTotalCount());
    }

    /**
     * {@inheritdoc}
     */
    public function getItemClass()
    {
        return GroupMember::class;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsFulltextSearch()
    {
        return true;
    }
}
