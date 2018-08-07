<?php

namespace MakinaCorpus\Ucms\Seo\Page;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Dashboard\Page\SearchForm;
use MakinaCorpus\Ucms\Dashboard\Page\SortManager;

class NodeRedirectDatasource extends AbstractDatasource
{
    use StringTranslationTrait;

    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     */
    public function __construct(\DatabaseConnection $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortFields($query)
    {
        return [
            'path' => $this->t("Path"),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getItems($query, PageState $pageState)
    {
        if (empty($query['node'])) {
            return [];
        }

        $q = $this->db->select('ucms_seo_redirect', 'u');
        $q->fields('u');
        $q->condition('u.nid', $query['node']);

        if ($pageState->hasSortField()) {
            $q->orderBy(
                'u.'.$pageState->getSortField(),
                SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc'
            );
        }

        $sParam = SearchForm::DEFAULT_PARAM_NAME;
        if (!empty($query[$sParam])) {
            $q->condition('u.path', '%'.db_like($query[$sParam]).'%', 'LIKE');
        }

        return $q
            ->extend('PagerDefault')
            ->limit($pageState->getLimit())
            ->execute()
            ->fetchAll()
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
