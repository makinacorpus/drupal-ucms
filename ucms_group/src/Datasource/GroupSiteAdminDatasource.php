<?php

namespace MakinaCorpus\Ucms\Group\Datasource;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Calista\Datasource\AbstractDatasource;
use MakinaCorpus\Calista\Datasource\Filter;
use MakinaCorpus\Calista\Datasource\Query;
use MakinaCorpus\Drupal\Calista\Datasource\QueryExtender\DrupalPager;
use MakinaCorpus\Ucms\Site\GroupManager;
use MakinaCorpus\Ucms\Site\GroupSite;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;

class GroupSiteAdminDatasource extends AbstractDatasource
{
    use StringTranslationTrait;

    private $database;
    private $groupManager;
    private $siteManager;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param GroupManager $groupManager
     */
    public function __construct(\DatabaseConnection $database, GroupManager $groupManager, SiteManager $siteManager)
    {
        $this->database = $database;
        $this->groupManager = $groupManager;
        $this->siteManager = $siteManager;
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
            (new Filter('group', $this->t("Group"))),
            (new Filter('theme', $this->t("Theme")))->setChoicesMap($this->siteManager->getAllowedThemesOptionList()),
            (new Filter('template', $this->t("Template")))->setChoicesMap($this->siteManager->getTemplateList()),
            (new Filter('other', $this->t("Other")))->setChoicesMap(['t' => "template"]),
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
            's.state'       => $this->t("state"),
            's.type'        => $this->t("type"),
            's.http_host'   => $this->t("domain name"),
        ];
    }

    /**
     * {@inheritdoc}
     *
    public function getDefaultSort()
    {
        return ['s.title', SortManager::ASC];
    }
     */

    /**
     * {@inheritdoc}
     */
    public function getItems(Query $query)
    {
        $q = $this
            ->database
            ->select('ucms_site', 's')
            ->addTag('ucms_group_access')
            ->addTag('ucms_site_access')
            //->groupBy('s.id')
        ;

        // We need aliases
        $q->addField('s', 'id', 'site_id');
        $q->addField('s', 'group_id', 'group_id');

        if ($query->has('group')) {
            $q->condition('s.group_id', $query->get('group'));
        }
        if ($query->has('site')) {
            $q->condition('s.id', $query->get('site'));
        }

        // Filters
        if ($query->has('state')) {
            $q->condition('s.state', $query->get('state'));
        }
        if ($query->has('theme')) {
            $q->condition('s.theme', $query->get('theme'));
        }
        if ($query->has('template')) {
            $q->condition('s.template_id', $query->get('template'));
        }

        if ($query->hasSortField()) {
            $q->orderBy($query->getSortField(), $query->getSortOrder());
        }
        $q->orderBy('s.title', $query->getSortOrder());

        $search = $query->getSearchString();
        if ($search) {
            $q->condition(
                (new \DatabaseCondition('OR'))
                    ->condition('s.title', '%' . db_like($search) . '%', 'LIKE')
                    ->condition('s.title_admin', '%' . db_like($search) . '%', 'LIKE')
                    ->condition('s.http_host', '%' . db_like($search) . '%', 'LIKE')
            );
        }

        /** @var \MakinaCorpus\Drupal\Calista\Datasource\QueryExtender\DrupalPager $pager */
        $pager = $q->extend(DrupalPager::class)->setDatasourceQuery($query);
        $r = $pager->execute();
        $r->setFetchMode(\PDO::FETCH_CLASS, GroupSite::class);

        $ret = $r->fetchAll();

        // Preload all sites, we will need it in display
        $siteIdList = array_map(function (GroupSite $item) { return $item->getSiteId(); }, $ret);
        $sites = $this->siteManager->getStorage()->loadAll($siteIdList, false);
        /** @var \MakinaCorpus\Ucms\Site\GroupSite $record */
        foreach ($ret as $record) {
            $siteId = $record->getSiteId();
            if (isset($sites[$siteId])) {
                $record->setSite($sites[$siteId]);
            }
        }

        return $this->createResult($ret, $pager->getTotalCount());
    }

    /**
     * {@inheritdoc}
     */
    public function getItemClass()
    {
        return GroupSite::class;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsFulltextSearch()
    {
        return true;
    }
}
