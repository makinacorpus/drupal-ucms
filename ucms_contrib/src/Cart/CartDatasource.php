<?php

namespace MakinaCorpus\Ucms\Contrib\Cart;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Dashboard\Page\SortManager;

class CartDatasource extends AbstractDatasource
{
    private $userId;
    private $cart;

    /**
     * Default constructor
     */
    public function __construct($userId, CartStorageInterface $cart)
    {
        $this->userId = $userId;
        $this->cart = $cart;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters($query)
    {
        return [];
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
        // State is not supported yet, really.
        return $this->cart->listFor($this->userId, $pageState->getLimit(), $pageState->getOffset());
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
