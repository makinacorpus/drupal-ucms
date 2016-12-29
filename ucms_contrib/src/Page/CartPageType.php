<?php

namespace MakinaCorpus\Ucms\Contrib\Page;

use MakinaCorpus\Ucms\Contrib\Cart\CartDatasource;
use MakinaCorpus\Ucms\Contrib\Cart\CartStorageInterface;
use MakinaCorpus\Ucms\Dashboard\Page\PageBuilder;
use MakinaCorpus\Ucms\Dashboard\Page\PageTypeInterface;

use Symfony\Component\HttpFoundation\Request;

class CartPageType implements PageTypeInterface
{
    private $cart;
    private $datasource;

    /**
     * Default constructor
     *
     * @param DatasourceInterface $datasource
     */
    public function __construct(CartStorageInterface $cart)
    {
        $this->cart = $cart;
    }

    /**
     * {@inheritdoc}
     */
    public function getDatasource()
    {
        if (!$this->datasource) {
            $this->datasource = new CartDatasource($this->cart);
        }

        return $this->datasource;
    }

    /**
     * {@inheritdoc}
     */
    public function build(PageBuilder $builder, Request $request)
    {
        if ($this->cart->isReadonly()) {
            $builder
                ->setAllowedTemplates([
                    'cart-readonly' => 'module:ucms_contrib:views/Page/page-cart-readonly.html.twig',
                ])
                ->setDefaultDisplay('cart-readonly')
                ->setDatasource($this->getDatasource())
            ;
        } else{
            $builder
                ->setAllowedTemplates([
                    'cart' => 'module:ucms_contrib:views/Page/page-cart.html.twig',
                ])
                ->setDefaultDisplay('cart')
                ->setDatasource($this->getDatasource())
            ;
        }
    }
}
