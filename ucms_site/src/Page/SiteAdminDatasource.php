<?php

namespace MakinaCorpus\Ucms\Site\Page;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Ucms\Dashboard\Page\LinksFilterDisplay;
use MakinaCorpus\Ucms\Site\SiteAccessService;
use MakinaCorpus\Ucms\Site\SiteFinder;
use MakinaCorpus\Ucms\Site\SiteState;

class SiteAdminDatasource implements DatasourceInterface
{
    use StringTranslationTrait;

    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * @var SiteFinder
     */
    private $finder;

    /**
     * @var SiteAccessService
     */
    private $access;

    /**
     * @var SiteAdminDisplay
     */
    private $display;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param SiteFinder $finder
     * @param SiteAccessService $access
     */
    public function __construct(\DatabaseConnection $db, SiteFinder $finder, SiteAccessService $access)
    {
        $this->db = $db;
        $this->finder = $finder;
        $this->access = $access;
        $this->display = new SiteAdminDisplay();
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters($query)
    {
        return [
            (new LinksFilterDisplay('state', "State"))->setChoicesMap(SiteState::getList()),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * {@inheritdoc}
     */
    public function init($query) {}

    /**
     * {@inheritdoc}
     */
    public function getItems($query)
    {
        $limit = 24;

        $q = $this->db->select('ucms_site', 's');
        $q->leftJoin('users', 'u', "u.uid = s.uid");

        if (isset($query['state'])) {
            $q->condition('s.state', $query['state']);
        }

        $idList = $q
            ->fields('s', ['id'])
            ->extend('PagerDefault')
            ->extend('TableSort')
            ->orderByHeader($this->display->getTableHeaders())
            ->limit($limit)
            ->execute()
            ->fetchCol()
        ;

        return $this->finder->loadAll($idList);
    }

    /**
     * {@inheritdoc}
     */
    public function getItemActions($item)
    {
        /* @var $item \MakinaCorpus\Ucms\Site\Site */
        $ret = [];

        if ($this->access->userCanView($item)) {
            $ret[] = new Action($this->t("Details"), 'admin/dashboard/site/' . $item->id);
        }
        if ($this->access->userCanManage($item)) {
            $ret[] = new Action($this->t("Edit"), 'admin/dashboard/site/' . $item->id . '/edit');
        }
        if ($this->access->userCanManageWebmasters($item)) {
            $ret[] = new Action($this->t("Manage webmasters"), 'admin/dashboard/site/' . $item->id . '/webmasters');
        }
        if ($this->access->userCanDelete($item)) {
            $ret[] = new Action($this->t("Delete"), 'admin/dashboard/site/' . $item->id . '/delete');
        }

        // FIXME: Missing state-transition site state transformation
        //   missing 'change to state ...'
        //   missing 'approve change state to ...'

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function hasSearchForm()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchFormParamName()
    {
        return 's';
    }
 }
