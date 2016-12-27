<?php

namespace MakinaCorpus\Ucms\Dashboard\Page;

use Symfony\Component\HttpFoundation\Request;

/**
 * Default page implementation suitable for node datasources
 */
class DefaultNodePageType implements PageTypeInterface
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
    public function build(PageBuilder $builder, Request $request)
    {
        $builder
            ->setAllowedTemplates([
                'grid' => 'module:ucms_dashboard:views/Page/page-grid.html.twig',
                'table' => 'module:ucms_dashboard:views/Page/page.html.twig',
            ])
            ->setDefaultDisplay('table')
            ->setDatasource($this->datasource)
        ;
    }
}
