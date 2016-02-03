<?php

namespace MakinaCorpus\Ucms\Site\Page;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Ucms\Dashboard\Page\LinksFilterDisplay;
use MakinaCorpus\Ucms\Dashboard\Page\SortManager;
use MakinaCorpus\Ucms\Site\SiteState;
use MakinaCorpus\Ucms\Site\SiteStorage;

class SiteAdminDatasource extends AbstractDatasource
{
    use StringTranslationTrait;

    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * @var SiteStorage
     */
    private $storage;

    /**
     * @var SiteAdminDisplay
     */
    private $display;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param SiteStorage $storage
     * @param SiteAccessService $access
     */
    public function __construct(\DatabaseConnection $db, SiteStorage $storage)
    {
        $this->db = $db;
        $this->storage = $storage;
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
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * {@inheritdoc}
     */
    public function getItems($query, $sortField = null, $sortOrder = SortManager::DESC)
    {
        $limit = 24;

        $q = $this->db->select('ucms_site', 's');
        $q->leftJoin('users', 'u', "u.uid = s.uid");

        if (isset($query['state'])) {
            $q->condition('s.state', $query['state']);
        }

        if ($sortField) {
            $q->orderBy($sortField, SortManager::DESC === $sortOrder ? 'desc' : 'asc');
        }

        $idList = $q
            ->fields('s', ['id'])
            ->extend('PagerDefault')
            ->limit($limit)
            ->execute()
            ->fetchCol()
        ;

        return $this->storage->loadAll($idList);
    }
 }
