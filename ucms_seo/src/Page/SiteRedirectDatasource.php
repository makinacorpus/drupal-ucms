<?php

namespace MakinaCorpus\Ucms\Seo\Page;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Drupal\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Drupal\Dashboard\Page\PageState;
use MakinaCorpus\Drupal\Dashboard\Page\QueryExtender\DrupalPager;
use MakinaCorpus\Drupal\Dashboard\Page\SortManager;
use MakinaCorpus\Ucms\Seo\Path\Redirect;

/**
 * Site redirects datasource.
 */
class SiteRedirectDatasource extends AbstractDatasource
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
            'path'    => $this->t("path"),
            'node'    => $this->t("content title"),
            'expires' => $this->t("expires at"),
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

        $q = $this->db->select('ucms_seo_redirect', 'u');
        $q->fields('u');
        $q->condition('u.site_id', $query['site']);

        if ($pageState->hasSortField()) {

            switch ($pageState->getSortField()) {
                case 'path':
                    $sortField = 'u.path';
                    break;
                case 'site':
                    $sortField = 'n.title';
                    break;
                case 'expires':
                    $sortField = 'u.expires';
                    break;
            }

            $q->orderBy($sortField, SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc');
        }

        $sParam = 's';
        if (!empty($query[$sParam])) {
            $q->condition('u.path', '%'.db_like($query[$sParam]).'%', 'LIKE');
        }

        // @todo this could be better, we are supposed to already have the site!
        $q->join('node', 'n', "n.nid = u.nid");
        $q->addField('n', 'title', 'node_title');
        $q->join('ucms_site', 's', "s.id = u.site_id");
        $q->addField('s', 'title_admin', 'site_title');

        return $q
            ->extend(DrupalPager::class)
            ->setPageState($pageState)
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
