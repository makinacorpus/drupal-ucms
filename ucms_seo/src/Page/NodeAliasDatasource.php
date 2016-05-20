<?php

namespace MakinaCorpus\Ucms\Seo\Page;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Ucms\Dashboard\Page\LinksFilterDisplay;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Dashboard\Page\SearchForm;
use MakinaCorpus\Ucms\Dashboard\Page\SortManager;

class NodeAliasDatasource extends AbstractDatasource
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
            (new LinksFilterDisplay('canonical', $this->t("Is canonical")))->setChoicesMap([
                1 => $this->t("Yes"),
                0 => $this->t("No"),
            ]),
            (new LinksFilterDisplay('expires', $this->t("Do expire")))->setChoicesMap([
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
            'alias'         => $this->t("Alias"),
            'is_canonical'  => $this->t("Canonical state"),
            'language'      => $this->t("Language"),
            'expires'       => $this->t("Expiry date"),
            'priority'      => $this->t("Priority"),
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

        $q = $this->db->select('ucms_seo_alias', 'u');
        $q->fields('u');
        $q->condition('u.source', 'node/' . $query['node']);

        if (isset($query['canonical'])) {
            $q->condition('u.is_canonical', (int)(bool)$query['canonical']);
        }
        if (isset($query['expires'])) {
            if ($query['expires']) {
                $q->isNotNull('u.expires');
            } else {
                $q->isNull('u.expires');
            }
        }

        if ($pageState->hasSortField()) {
            $q->orderBy('u.' . $pageState->getSortField(), SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc');
        }
        $q->orderBy('u.alias', SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc');

        $sParam = SearchForm::DEFAULT_PARAM_NAME;
        if (!empty($query[$sParam])) {
            $q->condition('u.alias', '%' . db_like($query[$sParam]) . '%', 'LIKE');
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
