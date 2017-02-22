<?php

namespace MakinaCorpus\Ucms\Seo\Page;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Drupal\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Drupal\Dashboard\Page\Filter;
use MakinaCorpus\Drupal\Dashboard\Page\PageState;
use MakinaCorpus\Drupal\Dashboard\Page\QueryExtender\DrupalPager;
use MakinaCorpus\Drupal\Dashboard\Page\SortManager;

class SiteAliasDatasource extends AbstractDatasource
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
    public function getFilters($query)
    {
        return [
            (new Filter('outdated', $this->t("Is outdated")))->setChoicesMap([
                1 => $this->t("Yes"),
                0 => $this->t("No"),
            ]),
            (new Filter('custom', $this->t("Is custom")))->setChoicesMap([
                1 => $this->t("Yes"),
                0 => $this->t("No"),
            ]),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSortFields($query)
    {
        return [
            'alias'     => $this->t("alias"),
            'node'      => $this->t("content title"),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSort()
    {
        return ['alias', SortManager::ASC];
    }

    /**
     * {@inheritdoc}
     */
    public function getItems($query, PageState $pageState)
    {
        if (empty($query['site'])) {
            return [];
        }

        $q = $this->db->select('ucms_seo_route', 'u');
        $q->fields('u');
        $q->condition('u.site_id', $query['site']);
        $q->join('node', 'n', "n.nid = u.node_id");
        $q->addField('n', 'title', 'node_title');

        if (isset($query['outdated'])) {
            $q->condition('u.is_outdated', (int)(bool)$query['outdated']);
        }
        if (isset($query['custom'])) {
            $q->condition('u.is_protected', (int)(bool)$query['custom']);
        }

        if ($pageState->hasSortField()) {
            switch ($pageState->getSortField()) {
                case 'alias':
                    $sortField = 'u.route';
                    break;
                case 'node':
                    $sortField = 'n.title';
                    break;
            }

            $q->orderBy($sortField, SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc');
        } else {
            $q->orderBy('u.route', SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc');
        }

        // Consistent sorting
        $q->orderBy('u.node_id', SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc');

        $sParam = $pageState->getSearchParameter();
        if (!empty($query[$sParam])) {
            $q->condition(
                (new \DatabaseCondition('OR'))
                    ->condition('u.route', '%' . db_like($query[$sParam]) . '%', 'LIKE')
                    ->condition('n.title', '%' . db_like($query[$sParam]) . '%', 'LIKE')
            );
        }

        return $q
            ->extend(DrupalPager::class)
            ->setPageState($pageState)
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
