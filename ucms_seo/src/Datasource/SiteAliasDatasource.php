<?php

namespace MakinaCorpus\Ucms\Seo\Datasource;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Calista\Datasource\AbstractDatasource;
use MakinaCorpus\Calista\Datasource\Filter;
use MakinaCorpus\Calista\Datasource\Query;
use MakinaCorpus\Drupal\Calista\Datasource\QueryExtender\DrupalPager;

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
    public function getFilters()
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
            new Filter('site'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSorts()
    {
        return [
            'alias' => $this->t("alias"),
            'node'  => $this->t("content title"),
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

        $q = $this->db->select('ucms_seo_route', 'u');
        $q->fields('u');
        $q->condition('u.site_id', $query->get('site'));
        $q->join('node', 'n', "n.nid = u.node_id");
        $q->addField('n', 'title', 'node_title');

        if ($query->has('outdated')) {
            $q->condition('u.is_outdated', (int)(bool)$query->get('outdated'));
        }
        if ($query->has('custom')) {
            $q->condition('u.is_protected', (int)(bool)$query->get('custom'));
        }

        if ($query->hasSortField()) {
            switch ($query->getSortField()) {
                case 'alias':
                    $sortField = 'u.route';
                    break;
                case 'node':
                    $sortField = 'n.title';
                    break;
            }

            $q->orderBy($sortField, $query->getSortOrder());
        } else {
            $q->orderBy('u.route', $query->getSortOrder());
        }

        // Consistent sorting
        $q->orderBy('u.node_id', $query->getSortOrder());

        $search = $query->getSearchString();
        if ($search) {
            $q->condition(
                (new \DatabaseCondition('OR'))
                    ->condition('u.route', '%' . db_like($search) . '%', 'LIKE')
                    ->condition('n.title', '%' . db_like($search) . '%', 'LIKE')
            );
        }

        /** @var \MakinaCorpus\Drupal\Calista\Datasource\QueryExtender\DrupalPager $pager */
        $pager = $q->extend(DrupalPager::class)->setDatasourceQuery($query);
        $items = $pager->execute()->fetchAll();

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
