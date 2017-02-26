<?php

namespace MakinaCorpus\Ucms\Contrib\Page;

use Drupal\Core\Session\AccountInterface;
use MakinaCorpus\Drupal\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Drupal\Dashboard\Page\PageBuilder;
use MakinaCorpus\Drupal\Dashboard\Page\PageTypeInterface;
use Symfony\Component\HttpFoundation\Request;

class CartPageType implements PageTypeInterface
{
    private $datasource;
    private $account;
    private $readonly = true;

    /**
     * Default constructor
     *
     * @param DatasourceInterface $datasource
     */
    public function __construct(DatasourceInterface $datasource, AccountInterface $account, $readonly = true)
    {
        $this->datasource = $datasource;
        $this->account = $account;
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
        $builder
            ->setDatasource($this->datasource)
            ->addBaseQueryParameter('user_id', $this->account->id())
        ;

        if ($this->readonly) {
            $builder
                ->setAllowedTemplates([
                    'cart-readonly' => 'module:ucms_contrib:views/Page/page-cart-readonly.html.twig',
                ])
                ->setDefaultDisplay('cart-readonly')
            ;
        } else{
            $builder
                ->setAllowedTemplates([
                    'cart' => 'module:ucms_contrib:views/Page/page-cart.html.twig',
                ])
                ->setDefaultDisplay('cart')
            ;
        }
    }
}
