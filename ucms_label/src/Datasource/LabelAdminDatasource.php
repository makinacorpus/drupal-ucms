<?php

namespace MakinaCorpus\Ucms\Label\Datasource;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Calista\Datasource\AbstractDatasource;
use MakinaCorpus\Calista\Datasource\Filter;
use MakinaCorpus\Calista\Datasource\Query;
use MakinaCorpus\Drupal\Calista\Datasource\QueryExtender\DrupalPager;
use MakinaCorpus\Ucms\Label\LabelManager;

class LabelAdminDatasource extends AbstractDatasource
{
    use StringTranslationTrait;

    private $db;
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
    public function getFilters()
    {
        $categories = [];
        foreach ($this->manager->loadRootLabels() as $label) {
            $categories[$label->tid] = $label->name;
        }

        $statuses = [
            0 => $this->t("Editable"),
            1 => $this->t("Non editable"),
        ];

        return [
            (new Filter('category', $this->t("Category")))->setChoicesMap($categories),
            (new Filter('status', $this->t("Status")))->setChoicesMap($statuses),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSorts()
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
    public function getItems(Query $query)
    {
        $q = $this->db->select('taxonomy_term_data', 't');
        // fields() must be call prior to orderBy() because orderBy() will
        // also add the fields to the field list, thus change the order they
        // are SELECT'ed and make fetchCol() return the sorted field instead
        // of the one added right here.
        $q->fields('t', ['tid']);
        $q->join('taxonomy_term_hierarchy', 'h', "h.tid = t.tid");

        if ($query->has('category')) {
            $q->condition('h.parent', $query->get('category'));
        }
        if ($query->has('status')) {
            $q->condition('t.is_locked', $query->get('status'));
        }

        if ($query->hasSortField()) {
            $q->orderBy($query->getSortField(), $query->getSortOrder());
        }

        $q->condition('t.vid', $this->manager->getVocabularyId());

        /** @var \MakinaCorpus\Drupal\Calista\Datasource\QueryExtender\DrupalPager $pager */
        $pager = $q->extend(DrupalPager::class)->setDatasourceQuery($query);
        $idList = $pager->execute()->fetchCol();

        $items = $this->manager->loadLabels($idList);

        return $this->createResult($items, $pager->getTotalCount());
    }
 }

