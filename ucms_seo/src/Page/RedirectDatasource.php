<?php

namespace MakinaCorpus\Ucms\Seo\Page;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Dashboard\Page\SearchForm;
use MakinaCorpus\Ucms\Dashboard\Page\SortManager;
use MakinaCorpus\Ucms\Seo\Path\Redirect;
use MakinaCorpus\Ucms\Seo\Path\RedirectStorageInterface;

class RedirectDatasource extends AbstractDatasource
{
    use StringTranslationTrait;

    private $database;
    private $storage;

    /**
     * Default constructor
     */
    public function __construct(\DatabaseConnection $database, RedirectStorageInterface $storage)
    {
        $this->database = $database;
        $this->storage = $storage;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortFields($query)
    {
        return ['path' => $this->t("Path")];
    }

    /**
     * {@inheritdoc}
     */
    public function getItems($query, PageState $pageState)
    {
        $select = $this->storage->createSelectQuery();

        if (isset($query['site'])) {
            $select->condition('u.site_id', (int)$query['site']);
        }
        if (isset($query['node'])) {
            $select->condition('u.nid', (int)$query['node']);
        }

        if ($pageState->hasSortField()) {
            $select->orderBy(
                'u.'.$pageState->getSortField(),
                SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc'
            );
        }

        $sParam = SearchForm::DEFAULT_PARAM_NAME;
        if (!empty($query[$sParam])) {
            $select->condition('u.path', '%'.\db_like($query[$sParam]).'%', 'LIKE');
        }

        return $select
            ->extend('PagerDefault')
            ->limit($pageState->getLimit())
            ->execute()
            ->fetchAll(\PDO::FETCH_CLASS, Redirect::class)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function hasSearchForm()
    {
        return true;
    }
}
