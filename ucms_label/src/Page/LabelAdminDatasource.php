<?php


namespace MakinaCorpus\Ucms\Label\Page;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Ucms\Dashboard\Page\LinksFilterDisplay;
use MakinaCorpus\Ucms\Dashboard\Page\SearchForm;
use MakinaCorpus\Ucms\Dashboard\Page\SortManager;
use MakinaCorpus\Ucms\Label\LabelManager;


class LabelAdminDatasource extends AbstractDatasource
{
    use StringTranslationTrait;


    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * @var LabelManager
     */
    private $manager;


    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param LabelManager $manager
     */
    public function __construct(\DatabaseConnection $db, LabelManager $manager)
    {
        $this->db = $db;
        $this->manager = $manager;
    }


    /**
     * {@inheritdoc}
     */
    public function getFilters($query)
    {
        $categories = [];
        foreach ($this->manager->loadRootLabels() as $label) {
            $categories[$label->tid] = $label->name;
        }

        $statuses = [
            1 => t("Non editable"),
            0 => t("Editable"),
        ];

        return [
            (new LinksFilterDisplay('category', $this->t("Category")))->setChoicesMap($categories),
            (new LinksFilterDisplay('status', $this->t("Status")))->setChoicesMap($statuses),
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function getSortFields($query)
    {
        return [
            't.tid'       => $this->t("identifier"),
            't.name'      => $this->t("name"),
            't.is_locked' => $this->t("status"),
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function getDefaultSort()
    {
        return ['t.name', SortManager::ASC];
    }


    /**
     * {@inheritdoc}
     */
    public function getItems($query, $sortField = null, $sortOrder = SortManager::DESC)
    {
        $limit = 24;

        $q = $this->db->select('taxonomy_term_data', 't');
        $q->join('taxonomy_term_hierarchy', 'h', "h.tid = t.tid");

        if (isset($query['category'])) {
            $q->condition('h.parent', $query['category']);
        }
        if (isset($query['status'])) {
            $q->condition('t.is_locked', $query['status']);
        }

        if ($sortField) {
            $q->orderBy($sortField, SortManager::DESC === $sortOrder ? 'desc' : 'asc');
        }

//        $sParam = SearchForm::DEFAULT_PARAM_NAME;
//        if (!empty($query[$sParam])) {
//            $q->condition('t.name', '%' . db_like($query[$sParam]) . '%', 'LIKE');
//        }

        $ids = $q
            ->fields('t', ['tid'])
            ->condition('t.vid', $this->manager->getVocabularyId())
            ->extend('PagerDefault')
            ->limit($limit)
            ->execute()
            ->fetchCol();

        return $this->manager->loadLabels($ids);
    }


    /**
     * {@inheritdoc}
     */
    public function hasSearchForm()
    {
        return false;
    }
 }
