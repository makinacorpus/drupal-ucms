<?php

namespace MakinaCorpus\Ucms\Contrib\Page;

use MakinaCorpus\Ucms\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Ucms\Dashboard\Page\PageBuilder;
use MakinaCorpus\Ucms\Dashboard\Page\PageTypeInterface;

use Symfony\Component\HttpFoundation\Request;

class CartPageType implements PageTypeInterface
{
    private $datasource;
    private $readonly = true;

    /**
     * Default constructor
     *
     * @param DatasourceInterface $datasource
     */
    public function __construct(DatasourceInterface $datasource, $readonly = true)
    {
        $this->datasource = $datasource;
        $this->readonly = $readonly;
    }

    /**
     * {@inheritdoc}
     */
    public function getDatasource()
    {
        return $this->datasource;
    }

    /**
     * {@inheritdoc}
     */
    public function build(PageBuilder $builder, Request $request)
    {
        if ($this->readonly) {
            $builder
                ->setAllowedTemplates([
                    'cart-readonly' => 'module:ucms_contrib:views/Page/page-cart-readonly.html.twig',
                ])
                ->setDefaultDisplay('cart-readonly')
                ->setDatasource($this->datasource)
            ;
        } else{
            $builder
                ->setAllowedTemplates([
                    'cart' => 'module:ucms_contrib:views/Page/page-cart.html.twig',
                ])
                ->setDefaultDisplay('cart')
                ->setDatasource($this->datasource)
            ;
        }
    }
}
