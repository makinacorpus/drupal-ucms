<?php

namespace MakinaCorpus\Ucms\Contrib\Datasource;

use Drupal\Core\Entity\EntityManager;
use Drupal\node\NodeInterface;
use MakinaCorpus\Calista\Datasource\Query;
use MakinaCorpus\Drupal\Calista\Datasource\DefaultNodeDatasource;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Calista\Datasource\Filter;

/**
 * Node datasource aware of sites and other ucms properties
 */
class NodeDatasource extends DefaultNodeDatasource
{
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
        parent::__construct($database, $entityManager);

        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        $ret = parent::getFilters();

        $ret[] = (new Filter('is_global', $this->t("Global")))->setChoicesMap([1 => t("Yes"), 0 => t("No")]);
        $ret[] = (new Filter('is_flagged', $this->t("Flagged")))->setChoicesMap([1 => t("Yes"), 0 => t("No")]);
        $ret[] = (new Filter('is_starred', $this->t("Starred")))->setChoicesMap([1 => t("Yes"), 0 => t("No")]);
        $ret[] = (new Filter('is_clonable', $this->t("Locked")))->setChoicesMap([0 => t("Yes"), 1 => t("No")]);
        $ret[] = (new Filter('is_group', $this->t("Group")))->setChoicesMap([1 => t("Yes"), 0 => t("No")]);
        // @todo site_id
        // @todo in my cart

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function getSorts()
    {
        return parent::getSorts() + [
            'n.is_flagged'  => $this->t("flag"),
        ];
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
    function preloadDependencies(array $nodeIdList)
    {
        $siteIdList = [];

        $nodeList = parent::preloadDependencies($nodeIdList);

        foreach ($nodeList as $node) {
            foreach ($node->ucms_sites as $siteId) {
                $siteIdList[$siteId] = $siteId;
            }
        }

        if ($siteIdList) {
            $this->siteManager->getStorage()->loadAll($siteIdList);
        }

        return $nodeList;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFilters(\SelectQuery $select, Query $query)
    {
        parent::applyFilters($select, $query);

        if ($query->has('is_global')) {
            $select->condition('n.is_global', $query->get('is_global'));
        }
        if ($query->has('is_flagged')) {
            $select->condition('n.is_flagged', $query->get('is_flagged'));
        }
        if ($query->has('is_starred')) {
            $select->condition('n.is_starred', $query->get('is_starred'));
        }
        if ($query->has('is_clonable')) {
            $select->condition('n.is_clonable', $query->get('is_clonable'));
        }

        if ($this->isSiteContextDependent()) {
            $select->addTag(Access::QUERY_TAG_CONTEXT_OPT_OUT);
        }
    }
}
