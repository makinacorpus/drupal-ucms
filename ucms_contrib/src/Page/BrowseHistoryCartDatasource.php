<?php

namespace MakinaCorpus\Ucms\Contrib\Page;

use MakinaCorpus\Ucms\Contrib\Cart\CartItem;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Dashboard\Page\SortManager;

/**
 * This implementation returns CartItem instances
 */
class BrowseHistoryCartDatasource extends AbstractNodeDatasource
{
    /**
     * {@inheritdoc}
     */
    protected function isSiteContextDependent()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSort()
    {
        return ['h.timestamp', SortManager::DESC];
    }

    /**
     * {@inheritdoc}
     */
    public function getItems($query, PageState $pageState)
    {
        if (empty($query['user_id'])) {
            return [];
        }
        $userId = $query['user_id'];

        $select = $this->getDatabase()->select('node', 'n');
        $select = $this->process($select, $query, $pageState);

        // JOIN with {history} is actually done in the parent implementation
        $select->fields('n', ['nid']);
        $select->fields('h', ['uid']);
        $select->addField('h', 'timestamp', 'added');
        $select->isNotNull('h.uid');

        $items = $select
            ->condition('h.uid', $userId)
            ->execute()
            ->fetchAll()
        ;

        $ret = [];

        $nodeIdList = [];
        foreach ($items as $item) {
            $nodeIdList[] = $item->nid;
            $ret[$item->nid] = new CartItem($item->nid, $userId, $item->added);
        }

        // Preload and set nodes at once
        $nodes = $this->preloadDependencies($nodeIdList);
        /** @var \MakinaCorpus\Ucms\Contrib\Cart\CartItem $item */
        foreach ($ret as $id => $item) {
            $nodeId = $item->getNodeId();
            if (!isset($nodes[$nodeId])) {
                // Very unlikely, but a node could have been deleted between
                // our very first query and this
                unset($ret[$id]);
                continue;
            }
            $item->setNode($nodes[$nodeId]);
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchFormParamName()
    {
        return 'cb';
    }
}
