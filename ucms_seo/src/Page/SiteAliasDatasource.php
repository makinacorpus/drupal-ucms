<?php

namespace MakinaCorpus\Ucms\Seo\Page;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Drupal\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Drupal\Dashboard\Page\Filter;
use MakinaCorpus\Drupal\Dashboard\Page\PageState;
use MakinaCorpus\Drupal\Dashboard\Page\SearchForm;
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
            (new Filter('canonical', $this->t("Is canonical")))->setChoicesMap([
                1 => $this->t("Yes"),
                0 => $this->t("No"),
            ]),
            (new Filter('expires', $this->t("Do expire")))->setChoicesMap([
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
        if (empty($query['site'])) {
            return [];
        }

        $q = $this->db->select('ucms_seo_alias', 'u');
        $q->fields('u');
        $q->condition('u.site_id', $query['site']);

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
