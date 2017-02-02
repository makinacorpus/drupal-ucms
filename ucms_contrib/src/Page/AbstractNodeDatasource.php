<?php

namespace MakinaCorpus\Ucms\Contrib\Page;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Drupal\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Drupal\Dashboard\Page\PageState;
use MakinaCorpus\Drupal\Dashboard\Page\SortManager;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\SiteManager;

abstract class AbstractNodeDatasource extends AbstractDatasource
{
    use StringTranslationTrait;

    private $database;
    private $entityManager;
    private $siteManager;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param EntityManager $entityManager
     * @param SiteManager $manager
     */
    public function __construct(\DatabaseConnection $database, EntityManager $entityManager, SiteManager $siteManager)
    {
        $this->database = $database;
        $this->entityManager = $entityManager;
        $this->siteManager = $siteManager;
    }

    final protected function getDatabase()
    {
        return $this->database;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters($query)
    {
        // @todo build commong database filters for node datasource into some
        //   trait or abstract implemetnation to avoid duplicates
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getSortFields($query)
    {
        return [
            'n.created'     => $this->t("creation date"),
            'n.changed'     => $this->t("lastest update date"),
            'h.timestamp'   => $this->t('most recently viewed'),
            'n.status'      => $this->t("status"),
            'n.uid'         => $this->t("owner"),
            'n.title.title' => $this->t("title"),
            'n.is_flagged'  => $this->t("flag"),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSort()
    {
        return ['n.changed', SortManager::DESC];
    }

    /**
     * Override and this to false to desactivate site context filtering
     *
     * @return boolean
     */
    protected function isSiteContextDependent()
    {
        return true;
    }

    /**
     * Preload pretty much everything to make admin listing faster
     *
     * You should call this.
     *
     * @param int[] $nodeIdList
     *
     * @return NodeInterface[]
     *   The loaded nodes
     */
    final protected function preloadDependencies(array $nodeIdList)
    {
        $userIdList = [];
        $siteIdList = [];

        $nodeList = $this->entityManager->getStorage('node')->loadMultiple($nodeIdList);

        foreach ($nodeList as $node) {
            $userIdList[$node->uid] = $node->uid;
            foreach ($node->ucms_sites as $siteId) {
                $siteIdList[$siteId] = $siteId;
            }
        }

        if ($userIdList) {
            $this->entityManager->getStorage('user')->loadMultiple($userIdList);
        }
        if ($siteIdList) {
            $this->siteManager->getStorage()->loadAll($siteIdList);
        }

        return $nodeList;
    }

    /**
     * Implementors must set the node table with 'n' as alias, and call this
     * method for the datasource to work correctly.
     *
     * @param \SelectQuery $select
     * @param mixed[] $query
     * @param PageState $pageState
     *
     * @return \SelectQuery
     *   It can be an extended query, so use this object.
     */
    final protected function process(\SelectQuery $select, $query, PageState $pageState)
    {
        if ($pageState->hasSortField()) {
            $select->orderBy($pageState->getSortField(), SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc');
        }
        $select->orderBy('n.nid', SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc');

        $sParam = $pageState->getSearchParameter();
        if (!empty($query[$sParam])) {
            $select->condition('n.title', '%' . db_like($query[$sParam]) . '%', 'LIKE');
        }

        // Also add a few joins
        $select->leftJoin('history', 'h', "h.nid = n.nid");

        // @todo here would be the rigth place to deal with filters

        if ($this->isSiteContextDependent()) {
            $select->addTag(Access::QUERY_TAG_CONTEXT_OPT_OUT);
        }

        return $select
            ->addTag('node_access')
            //->groupBy('n.nid')
            ->extend('PagerDefault')
            ->limit($pageState->getLimit())
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function hasSearchForm()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchFormParamName()
    {
        return 's';
    }
}
