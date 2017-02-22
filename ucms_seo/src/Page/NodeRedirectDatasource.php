<?php

namespace MakinaCorpus\Ucms\Seo\Page;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Drupal\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Drupal\Dashboard\Page\PageState;
use MakinaCorpus\Drupal\Dashboard\Page\QueryExtender\DrupalPager;
use MakinaCorpus\Drupal\Dashboard\Page\SortManager;
use MakinaCorpus\Ucms\Seo\Path\Redirect;

/**
 * Node redirects datasource.
 */
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
            'path'    => $this->t("path"),
            'site'    => $this->t("site"),
            'expires' => $this->t("expires at"),
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
            switch ($pageState->getSortField()) {
                case 'path':
                    $sortField = 'u.path';
                    break;
                case 'site':
                    $sortField = 's.title_admin';
                    break;
                case 'expires':
                    $sortField = 'u.expires';
                    break;
            }

            $q->orderBy($sortField, SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc');
        }

        $sParam = $pageState->getSearchParameter();
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
