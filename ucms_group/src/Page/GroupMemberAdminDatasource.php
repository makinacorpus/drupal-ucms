<?php

namespace MakinaCorpus\Ucms\Group\Page;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Drupal\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Drupal\Dashboard\Page\PageState;
use MakinaCorpus\Drupal\Dashboard\Page\SortManager;
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
    public function getFilters($query)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getSortFields($query)
    {
        return [
            'gu.user_id'  => $this->t("member identifier"),
            'u.name'      => $this->t("member name"),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSort()
    {
        return ['u.name', SortManager::ASC];
    }

    /**
     * {@inheritdoc}
     */
    public function getItems($query, PageState $pageState)
    {
        $q = $this
            ->database
            ->select('ucms_group_user', 'gu')
            ->fields('gu', ['group_id', 'user_id'])
            ->fields('u', ['name', 'mail', 'status'])
        ;

        $q->join('users', 'u', "u.uid = gu.user_id");

        if (!empty($query['group'])) {
            $q->condition('gu.group_id', $query['group']);
        }
        if (!empty($query['uid'])) {
            $q->condition('gu.user_id', $query['uid']);
        }

        if ($pageState->hasSortField()) {
            $q->orderBy($pageState->getSortField(), SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc');
        }
        $q->orderBy('u.name', SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc');

        $sParam = $pageState->getSearchParameter();
        if (!empty($query[$sParam])) {
            $q->condition(
                (new \DatabaseCondition('OR'))
                    ->condition('u.name', '%' . db_like($query[$sParam]) . '%', 'LIKE')
                    ->condition('u.mail', '%' . db_like($query[$sParam]) . '%', 'LIKE')
            );
        }

        $r = $q
            ->addTag('ucms_group_access')
            ->groupBy('gu.user_id')
            ->extend('PagerDefault')
            ->limit($pageState->getLimit())
            ->execute()
        ;

        $r->setFetchMode(\PDO::FETCH_CLASS, GroupMember::class);

        return $r->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public function hasSearchForm()
    {
        return true;
    }
 }
