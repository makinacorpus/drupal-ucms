<?php

namespace MakinaCorpus\Ucms\Cart\Datasource;

use MakinaCorpus\Calista\Datasource\Filter;
use MakinaCorpus\Calista\Datasource\Query;
use MakinaCorpus\Ucms\Cart\Cart\CartItem;

/**
 * Datasource for carts.
 */
class CartDatasource extends NodeDatasource
{
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
        $ret = parent::getFilters();

        $ret[] = new Filter('cart_user_id', $this->t("User (cart owner)"));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortFields($query)
    {
        return parent::getSorts() + [
            'c.ts_added'    => $this->t("added to cart date"),
            'c.weight'      => $this->t("cart order"),
        ];
    }

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
        if ($query->has('cart_user_id')) {
            $userId = $query->get('cart_user_id');
            $select->condition('c.uid', $userId);
            $select->join('ucms_cart', 'c', "c.nid = n.nid");
        } else {
            // Avoid errors if people use the filter accidentally
            return $this->createEmptyResult();
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
        /** @var \MakinaCorpus\Ucms\Cart\Cart\CartItem $item */
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
