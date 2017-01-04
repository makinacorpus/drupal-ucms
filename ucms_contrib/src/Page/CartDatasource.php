<?php

namespace MakinaCorpus\Ucms\Contrib\Page;

use MakinaCorpus\Ucms\Contrib\Cart\CartItem;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Dashboard\Page\SortManager;

/**
 * Datasource for user favorites.
 *
 * @todo write SQL directly into this
 */
class CartDatasource extends AbstractNodeDatasource
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
    public function getSortFields($query)
    {
        return [
            'c.ts_added'    => $this->t("added to cart date"),
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
        return ['c.ts_added', SortManager::DESC];
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
        $select->join('ucms_contrib_cart', 'c', "c.nid = n.nid");

        // JOIN with {history} is actually done in the parent implementation
        $select->fields('n', ['nid']);
        $select->fields('h', ['uid']);
        $select->addField('h', 'timestamp', 'added');

        $items = $select
            ->condition('c.uid', $userId)
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
        return 'cs';
    }
}
