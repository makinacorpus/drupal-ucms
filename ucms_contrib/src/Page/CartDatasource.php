<?php

namespace MakinaCorpus\Ucms\Contrib\Page;

use MakinaCorpus\Calista\Datasource\Filter;
use MakinaCorpus\Calista\Datasource\Query;
use MakinaCorpus\Ucms\Contrib\Cart\CartItem;

/**
 * Datasource for carts.
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
    public function getItemClass()
    {
        return CartItem::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new Filter('type'),
            new Filter('user_id')
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSortFields($query)
    {
        return [
            'c.ts_added'    => $this->t("added to cart date"),
            'c.weight'      => $this->t("cart order"),
            'n.created'     => $this->t("creation date"),
            'n.changed'     => $this->t("lastest update date"),
            'h.timestamp'   => $this->t('most recently viewed'),
            'n.title'       => $this->t("title"),
        ];
    }

    /**
     * {@inheritdoc}
     *
    public function getDefaultSort()
    {
        return ['c.weight', SortManager::DESC];
    }
     */

    /**
     * {@inheritdoc}
     *
    public function getSearchFormParamName()
    {
        return 'cs';
    }
     */

    /**
     * Returns a column on which an arbitrary sort will be added in order to
     * ensure that besides user selected sort order, it will be  predictible
     * and avoid sort glitches.
     */
    protected function getPredictibleOrderColumn()
    {
        return 'c.ts_added';
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(Query $query)
    {
        $select = $this->getDatabase()->select('node', 'n');
        $select = $this->process($select, $query);

        // JOIN with {history} is actually done in the parent implementation
        $select->fields('n', ['nid']);
        $select->fields('h', ['uid']);
        $select->addField('h', 'timestamp', 'added');

        if ($query->has('type')) {
            $select->condition('n.type', $query->get('type'));
        }
        if ($query->has('user_id')) {
            $userId = $query->get('user_id');
            $select->condition('c.uid', $userId);
            $select->join('ucms_contrib_cart', 'c', "c.nid = n.nid");
        } else {
            // Avoid errors if people use the filter accidentally
            $select->leftJoin('ucms_contrib_cart', 'c', "c.nid = n.nid and 1 = 0");
            $userId = null;
        }

        $items = $select->execute()->fetchAll();

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

        return $this->createResult($ret);
    }
}
