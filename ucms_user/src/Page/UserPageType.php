<?php

namespace MakinaCorpus\Ucms\User\Page;

use MakinaCorpus\Drupal\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Drupal\Dashboard\Page\PageBuilder;
use MakinaCorpus\Drupal\Dashboard\Page\PageTypeInterface;

use Symfony\Component\HttpFoundation\Request;

class UserPageType implements PageTypeInterface
{
    private $datasource;

    /**
     * Default constructor
     *
     * @param DatasourceInterface $datasource
     */
    public function __construct(DatasourceInterface $datasource)
    {
        $this->datasource = $datasource;
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
            ->setAllowedTemplates([
                'table' => 'module:ucms_user:views/Page/page-user.html.twig',
            ])
            ->setDefaultDisplay('table')
            ->setDatasource($this->datasource)
        ;
    }
}
