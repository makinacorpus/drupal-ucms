<?php

namespace MakinaCorpus\Ucms\Tree\Datasource;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Calista\Datasource\AbstractDatasource;
use MakinaCorpus\Calista\Datasource\Filter;
use MakinaCorpus\Calista\Datasource\Query;
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
    public function getFilters()
    {
        $sites = $this->getWebmasterSites();

        if (count($sites) < 2) {
            return [];
        }

        return [
            (new Filter('site', $this->t("Site")))->setChoicesMap($sites),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(Query $query)
    {
        $sites = $this->getWebmasterSites();

        // User is not webmaster of any site, he can't see this
        if (empty($sites)) {
            return $this->createEmptyResult();
        }

        $conditions = [];

        if ($query->has('site')) {
            $siteId = $query->get('site');
            // User is not webmaster of the current site, disallow
            if (!isset($sites[$siteId])) {
                return [];
            }
            $conditions['site_id'] = $siteId;
        } else {
            $conditions['site_id'] = array_keys($sites);
        }

        $items = $this->menuStorage->loadWithConditions($conditions);

        return $this->createResult($items);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsPagination()
    {
        return false;
    }
 }
