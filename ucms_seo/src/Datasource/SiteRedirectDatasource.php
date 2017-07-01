<?php

namespace MakinaCorpus\Ucms\Seo\Datasource;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Seo\Path\Redirect;
use MakinaCorpus\Calista\Datasource\AbstractDatasource;
use MakinaCorpus\Calista\Datasource\Filter;
use MakinaCorpus\Calista\Datasource\Query;
use MakinaCorpus\Drupal\Calista\Datasource\QueryExtender\DrupalPager;

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
    public function getItemClass()
    {
        return Redirect::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new Filter('site'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSorts()
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
    public function getItems(Query $query)
    {
        if (!$query->has('site')) {
            return $this->createEmptyResult();
        }

        $q = $this->db->select('ucms_seo_redirect', 'u');
        $q->fields('u');
        $q->condition('u.site_id', $query->get('site'));

        if ($query->hasSortField()) {

            switch ($query->getSortField()) {
                case 'path':
                    $sortField = 'u.path';
                    break;
                case 'node':
                    $sortField = 'n.title';
                    break;
                case 'expires':
                    $sortField = 'u.expires';
                    break;
                default:
            }

            $q->orderBy($sortField, $query->getSortOrder());
        }

        $search = $query->getSearchString();
        if ($search) {
            $q->condition('u.path', '%'.db_like($search).'%', 'LIKE');
        }

        // @todo this could be better, we are supposed to already have the site!
        $q->join('node', 'n', "n.nid = u.nid");
        $q->addField('n', 'title', 'node_title');
        $q->join('ucms_site', 's', "s.id = u.site_id");
        $q->addField('s', 'title_admin', 'site_title');

        /** @var \MakinaCorpus\Drupal\Calista\Datasource\QueryExtender\DrupalPager $pager */
        $pager = $q->extend(DrupalPager::class)->setDatasourceQuery($query);
        $items = $pager->execute()->fetchAll(\PDO::FETCH_CLASS, Redirect::class);

        return $this->createResult($items, $pager->getTotalCount());
    }

    /**
     * {@inheritdoc}
     */
    public function supportsFulltextSearch()
    {
        return true;
    }
}
