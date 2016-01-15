<?php

namespace MakinaCorpus\Ucms\Site\Page;

use MakinaCorpus\Ucms\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Ucms\Dashboard\Page\LinksFilterDisplay;
use MakinaCorpus\Ucms\Site\SiteFinder;
use MakinaCorpus\Ucms\Site\State;

class SiteAdminDatasource implements DatasourceInterface
{
    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * @var SiteFinder
     */
    private $siteFinder;

    /**
     * @var SiteAdminDisplay
     */
    private $display;

    /**
     * Default constructor
     * @param SiteFinder $siteFinder
     */
    public function __construct(\DatabaseConnection $db, SiteFinder $siteFinder)
    {
        $this->db = $db;
        $this->siteFinder = $siteFinder;
        $this->display = new SiteAdminDisplay();
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters($query)
    {
        return [
            (new LinksFilterDisplay('state', "State"))->setChoicesMap(State::getList()),
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

        $q = $this
            ->db
            ->select('ucms_site', 's')
        ;

        $q->leftJoin('users', 'u', "u.uid = s.uid");

        // @todo Handle filters

        $idList = $q
            ->fields('s', ['id'])
            ->extend('PagerDefault')
            ->extend('TableSort')
            ->orderByHeader($this->display->getTableHeaders())
            ->limit($limit)
            ->execute()
            ->fetchCol()
        ;

        return $this->siteFinder->loadAll($idList);
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
