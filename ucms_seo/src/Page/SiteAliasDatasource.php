<?php

namespace MakinaCorpus\Ucms\Seo\Page;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Dashboard\Page\SearchForm;
use MakinaCorpus\Ucms\Dashboard\Page\SortManager;

class SiteAliasDatasource extends AbstractDatasource
{
    use StringTranslationTrait;

    private $database;

    /**
     * Default constructor
     */
    public function __construct(\DatabaseConnection $database)
    {
        $this->database = $database;
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
            'route' => $this->t("Alias"),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getItems($query, PageState $pageState)
    {
        if (empty($query['site'])) {
            return [];
        }

        $select = $this->database->select('ucms_seo_route', 'u');
        $select->fields('u');
        $select->condition('u.site_id', $query['site']);

        $select->leftJoin('node', 'n', "n.nid = u.node_id");
        $select->addField('n', 'nid', 'node_exists');
        $select->addField('n', 'title', 'node_title');
        $select->addField('n', 'type', 'node_type');

        $order = SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc';
        if ($pageState->hasSortField()) {
            $select->orderBy('u.' . $pageState->getSortField(), $order);
        }
        $select->orderBy('u.route', $order);

        $sParam = SearchForm::DEFAULT_PARAM_NAME;
        if (!empty($query[$sParam])) {
            $select->condition('u.route', '%' . \db_like($query[$sParam]) . '%', 'LIKE');
        }

        return $select
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
