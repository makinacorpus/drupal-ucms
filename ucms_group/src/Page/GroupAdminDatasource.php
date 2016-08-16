<?php

namespace MakinaCorpus\Ucms\Group\Page;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Dashboard\Page\SearchForm;
use MakinaCorpus\Ucms\Dashboard\Page\SortManager;
use MakinaCorpus\Ucms\Group\GroupManager;

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
            'g.id'          => $this->t("identifier"),
            'g.title'       => $this->t("title"),
            'g.ts_changed'  => $this->t("lastest update date"),
            'g.ts_created'  => $this->t("creation date"),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSort()
    {
        return ['g.ts_changed', SortManager::DESC];
    }

    /**
     * {@inheritdoc}
     */
    public function getItems($query, PageState $pageState)
    {
        $q = $this
            ->database
            ->select('ucms_group', 'g')
            ->fields('g', ['id'])
            ->addTag('ucms_group_access')
        ;

        if (!empty($query['uid'])) {
            $q->join('ucms_group_user', 'gu', "gu.group_id = g.id");
            $q->condition('gu.user_id', $query['uid']);
        }

        if ($pageState->hasSortField()) {
            $q->orderBy($pageState->getSortField(), SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc');
        }
        $q->orderBy('g.id', SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc');

        $sParam = SearchForm::DEFAULT_PARAM_NAME;
        if (!empty($query[$sParam])) {
            $q->condition('g.title', '%' . db_like($query[$sParam]) . '%', 'LIKE');
        }

        $idList = $q
            ->groupBy('g.id')
            ->extend('PagerDefault')
            ->limit($pageState->getLimit())
            ->execute()
            ->fetchCol()
        ;

        return $this->groupManager->getStorage()->loadAll($idList);
    }

    /**
     * {@inheritdoc}
     */
    public function hasSearchForm()
    {
        return true;
    }
 }
