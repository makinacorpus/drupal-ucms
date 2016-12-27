<?php

namespace MakinaCorpus\Ucms\Contrib\Cart;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Dashboard\Page\SortManager;

class CartDatasource extends AbstractDatasource
{
    private $cart;

    /**
     * Default constructor
     */
    public function __construct(CartStorageInterface $cart)
    {
        $this->cart = $cart;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters($query)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getSortFields($query)
    {
        return [
            'nid' => 'node identifier',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSort()
    {
        return ['nid', SortManager::DESC];
    }

    /**
     * {@inheritdoc}
     */
    public function getItems($query, PageState $pageState)
    {
        if (empty($query['user_id'])) {
            return [];
        }

        // State is not supported yet, really.
        return $this->cart->listFor($query['user_id'], $pageState->getLimit(), $pageState->getOffset());
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
        return 'cs';
    }
}
