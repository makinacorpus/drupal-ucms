<?php

namespace MakinaCorpus\Ucms\Contrib\Page;

use Drupal\Core\Session\AccountInterface;
use MakinaCorpus\Drupal\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Drupal\Dashboard\Page\PageBuilder;
use MakinaCorpus\Drupal\Dashboard\Page\PageTypeInterface;
use Symfony\Component\HttpFoundation\Request;
use MakinaCorpus\Ucms\Contrib\TypeHandler;

class CartPageType implements PageTypeInterface
{
    private $typeHandler;
    private $datasource;
    private $account;
    private $tab;

    /**
     * Default constructor
     *
     * Note that there is a hack here, the real user cart does not filters on
     * types, and such the type handler is not necessary, I was just too lazy
     * to write two different classes.
     *
     * @param TypeHandler $typeHandler
     * @param DatasourceInterface $datasource
     * @param AccountInterface $account
     * @param string $readonly
     */
    public function __construct(TypeHandler $typeHandler, DatasourceInterface $datasource, AccountInterface $account, $tab = null)
    {
        $this->typeHandler = $typeHandler;
        $this->datasource = $datasource;
        $this->account = $account;
        $this->tab = $tab;
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
            ->setLimit(16)
            ->addBaseQueryParameter('user_id', $this->account->id())
        ;

        if ($this->tab) {
            switch ($this->tab) {

                case 'content':
                    $builder->addBaseQueryParameter('type', $this->typeHandler->getContentTypes());
                    break;

                case 'media':
                    $builder->addBaseQueryParameter('type', $this->typeHandler->getMediaTypes());
                    break;
            }
        }

        $builder
            ->setAllowedTemplates([
                'cart' => 'module:ucms_contrib:views/Page/page-cart.html.twig',
            ])
            ->setDefaultDisplay('cart')
        ;
    }
}
