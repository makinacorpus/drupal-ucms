<?php

namespace MakinaCorpus\Ucms\Site\Page;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Ucms\Dashboard\Page\LinksFilterDisplay;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Dashboard\Page\SearchForm;
use MakinaCorpus\Ucms\Dashboard\Page\SortManager;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;

class SiteAdminDatasource extends AbstractDatasource
{
    use StringTranslationTrait;

    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * @var SiteManager
     */
    private $manager;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param SiteManager $manager
     */
    public function __construct(\DatabaseConnection $db, SiteManager $manager)
    {
        $this->db = $db;
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters($query)
    {
        return [
            (new LinksFilterDisplay('state', $this->t("State")))->setChoicesMap(SiteState::getList(SiteState::ARCHIVE)),
            // @todo missing site type registry or variable somewhere
            (new LinksFilterDisplay('theme', $this->t("Theme")))->setChoicesMap($this->manager->getAllowedThemesOptionList()),
            (new LinksFilterDisplay('template', $this->t("Template")))->setChoicesMap($this->manager->getTemplateList()),
            (new LinksFilterDisplay('other', $this->t("Other")))->setChoicesMap(['t' => "template"]),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSortFields($query)
    {
        return [
            's.id'          => $this->t("identifier"),
            's.title'       => $this->t("title"),
            's.http_host'   => $this->t("hostname"),
            's.state'       => $this->t("state"),
            's.type'        => $this->t("type"),
            's.ts_changed'  => $this->t("lastest update date"),
            's.ts_created'  => $this->t("creation date"),
            'u.name'        => $this->t("owner name"),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSort()
    {
        return ['s.ts_changed', SortManager::DESC];
    }

    /**
     * {@inheritdoc}
     */
    public function getItems($query, PageState $pageState)
    {
        $q = $this->db->select('ucms_site', 's');
        $q->leftJoin('users', 'u', "u.uid = s.uid");

        if (isset($query['state'])) {
            $q->condition('s.state', $query['state']);
        }
        if (isset($query['theme'])) {
            $q->condition('s.theme', $query['theme']);
        }
        if (isset($query['template'])) {
            $q->condition('s.template_id', $query['template']);
        }
        if (!empty($query['uid'])) {
            $q->join('ucms_site_access', 'sa', "sa.site_id = s.id");
            $q->condition('sa.uid', $query['uid']);
        }

        // Quite ugly, but working as of now
        // @todo find a more elegant way
        if (isset($query['other'])) {
            if (!is_array($query['other'])) {
                $query['other'] = [$query['other']];
            }
            foreach ($query['other'] as $value) {
                switch ($value) {
                    case 't':
                        $q->condition('s.is_template', 1);
                        break;
                }
            }
        }

        if ($pageState->hasSortField()) {
            $q->orderBy($pageState->getSortField(), SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc');
        }

        $sParam = SearchForm::DEFAULT_PARAM_NAME;
        if (!empty($query[$sParam])) {
            $q->condition(
                db_or()
                  ->condition('s.title', '%' . db_like($query[$sParam]) . '%', 'LIKE')
                  ->condition('s.http_host', '%' . db_like($query[$sParam]) . '%', 'LIKE')
            );
        }

        $idList = $q
            ->fields('s', ['id'])
            ->groupBy('s.id')
            ->extend('PagerDefault')
            ->limit($pageState->getLimit())
            ->execute()
            ->fetchCol()
        ;

        return $this->manager->getStorage()->loadAll($idList);
    }

    /**
     * {@inheritdoc}
     */
    public function hasSearchForm()
    {
        return true;
    }
 }
