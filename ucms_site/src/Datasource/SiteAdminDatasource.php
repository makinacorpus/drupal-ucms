<?php

namespace MakinaCorpus\Ucms\Site\Datasource;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Calista\Datasource\AbstractDatasource;
use MakinaCorpus\Calista\Datasource\Filter;
use MakinaCorpus\Calista\Datasource\Query;
use MakinaCorpus\Drupal\Calista\Datasource\QueryExtender\DrupalPager;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;
use MakinaCorpus\Ucms\Site\Site;

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
    public function getItemClass()
    {
        return Site::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        $states = SiteState::getList(SiteState::ARCHIVE);

        foreach ($states as $key => $label) {
          $states[$key] = $this->t($label);
        }

        return [
            (new Filter('state', $this->t("State")))->setChoicesMap($states),
            // @todo missing site type registry or variable somewhere
            (new Filter('theme', $this->t("Theme")))->setChoicesMap($this->manager->getAllowedThemesOptionList()),
            (new Filter('template', $this->t("Template")))->setChoicesMap($this->manager->getTemplateList()),
            (new Filter('other', $this->t("Other")))->setChoicesMap(['t' => "template"]),
            new Filter('uid'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSorts()
    {
        return [
            's.id'          => $this->t("identifier"),
            's.title'       => $this->t("title"),
            's.title_admin' => $this->t("administrative title"),
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
     *
    public function getDefaultSort()
    {
        return ['s.ts_changed', SortManager::DESC];
    }
     */

    /**
     * {@inheritdoc}
     */
    public function getItems(Query $query)
    {
        $q = $this
            ->db
            ->select('ucms_site', 's')
            ->fields('s', ['id'])
            ->addTag('ucms_site_access')
        ;
        $q->leftJoin('users', 'u', "u.uid = s.uid");

        if ($query->has('state')) {
            $q->condition('s.state', $query->get('state'));
        }
        if ($query->has('theme')) {
            $q->condition('s.theme', $query->get('theme'));
        }
        if ($query->has('template')) {
            $q->condition('s.template_id', $query->get('template'));
        }
        if ($query->has('uid')) {
            $q->join('ucms_site_access', 'sa', "sa.site_id = s.id");
            $q->condition('sa.uid', $query->get('uid'));
        }

        // Quite ugly, but working as of now
        // @todo find a more elegant way
        if ($query->has('other')) {
            $others = $query->get('other');
            if (!is_array($others)) {
                $others = [$others];
            }
            foreach ($others as $value) {
                switch ($value) {
                    case 't':
                        $q->condition('s.is_template', 1);
                        break;
                }
            }
        }

        if ($query->hasSortField()) {
            $q->orderBy($query->getSortField(), $query->getSortOrder());
        }
        $q->orderBy('s.id', $query->getSortOrder());

        $search = $query->getSearchString();
        if ($search) {
            $q->condition(
                db_or()
                  ->condition('s.title', '%' . db_like($search) . '%', 'LIKE')
                  ->condition('s.http_host', '%' . db_like($search) . '%', 'LIKE')
            );
        }

        $q->groupBy('s.id');

        /** @var \MakinaCorpus\Drupal\Calista\Datasource\QueryExtender\DrupalPager $pager */
        $pager = $q->extend(DrupalPager::class)->setDatasourceQuery($query);
        $idList = $pager->execute()->fetchCol();

        $items = $this->manager->getStorage()->loadAll($idList);

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
