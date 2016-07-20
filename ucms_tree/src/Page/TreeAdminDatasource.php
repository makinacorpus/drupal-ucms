<?php

namespace MakinaCorpus\Ucms\Tree\Page;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Ucms\Dashboard\Page\LinksFilterDisplay;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Dashboard\Page\SortManager;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Umenu\MenuStorageInterface;

class TreeAdminDatasource extends AbstractDatasource
{
    use StringTranslationTrait;

    private $menuStorage;
    private $siteManager;
    private $account;

    /**
     * Default constructor
     *
     * @param MenuStorageInterface $menuStorage
     * @param SiteManager $siteManager
     * @param AccountInterface $currentUser
     */
    public function __construct(MenuStorageInterface $menuStorage, SiteManager $siteManager, AccountInterface $currentUser)
    {
        $this->menuStorage = $menuStorage;
        $this->siteManager = $siteManager;
        $this->account = $currentUser;
    }

    /**
     * Get current account sites he's webmaster on
     *
     * @return string[]
     */
    private function getWebmasterSites()
    {
        $ret = [];

        foreach ($this->siteManager->loadWebmasterSites($this->account) as $site) {
            $ret[$site->getId()] = check_plain($site->title);
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters($query)
    {
        $sites = $this->getWebmasterSites();

        if (count($sites) < 2) {
            return [];
        }

        return [
            (new LinksFilterDisplay('site_id', $this->t("Site")))->setChoicesMap($sites),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSortFields($query)
    {
        /*
         * @todo
         *
        return [
            's.id'          => $this->t("identifier"),
            's.title'       => $this->t("title"),
            's.name'        => $this->t("technical name"),
            's.site_id'     => $this->t("site identifier"),
        ];
         */
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSort()
    {
        return ['s.site_id', SortManager::ASC];
    }

    /**
     * {@inheritdoc}
     */
    public function getItems($query, PageState $pageState)
    {
        $sites = $this->getWebmasterSites();

        // User is not webmaster of any site, he can't see this
        if (empty($sites)) {
            return [];
        }

        $conditions = [];

        if (isset($query['site_id'])) {
            // User is not webmaster of the current site, disallow
            if (!isset($sites[$query['site_id']])) {
                return [];
            }
            $conditions['site_id'] = $query['site_id'];
        } else {
            $conditions['site_id'] = array_keys($sites);
        }

        /*
         * @todo
         *
        if ($pageState->hasSortField()) {
            $q->orderBy($pageState->getSortField(), SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc');
        }
        $q->orderBy('s.id', SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc');

        $sParam = SearchForm::DEFAULT_PARAM_NAME;
        if (!empty($query[$sParam])) {
            $q->condition(
                db_or()
                  ->condition('s.title', '%' . db_like($query[$sParam]) . '%', 'LIKE')
                  ->condition('s.http_host', '%' . db_like($query[$sParam]) . '%', 'LIKE')
            );
        }
         */

        return $this->menuStorage->loadWithConditions($conditions);
    }

    /**
     * {@inheritdoc}
     */
    public function hasSearchForm()
    {
        return false;
    }
 }
