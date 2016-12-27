<?php

namespace MakinaCorpus\Ucms\Contrib\Page;

use MakinaCorpus\Ucms\Contrib\TypeHandler;
use MakinaCorpus\Ucms\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Ucms\Dashboard\Page\DefaultNodePageType;
use MakinaCorpus\Ucms\Dashboard\Page\PageBuilder;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\HttpFoundation\Request;

/**
 * Default page implementation suitable for admin node datasources
 */
class AdminNodePageType extends DefaultNodePageType
{
    private $datasource;
    private $typeHandler;
    private $siteManager;
    private $tab;
    private $filters;

    /**
     * Default constructor
     *
     * @param DatasourceInterface $datasource
     * @param TypeHandler $typeHandler
     * @param string $tab
     */
    public function __construct(DatasourceInterface $datasource, TypeHandler $typeHandler, SiteManager $siteManager, $tab = null, array $filters = [])
    {
        parent::__construct($datasource);

        $this->typeHandler = $typeHandler;
        $this->siteManager = $siteManager;
        $this->tab = $tab;
        $this->filters = $filters;
    }

    /**
     * {@inheritdoc}
     */
    public function build(PageBuilder $builder, Request $request)
    {
        parent::build($builder, $request);

        if ($this->filters) {
            foreach ($this->filters as $name => $value) {
                $builder->addBaseQueryParameter($name, $value);
            }
        }

        if ($this->siteManager->hasContext()) {
            $builder->addBaseQueryParameter('site_id', $this->siteManager->getContext()->getId());
        }

        if ($this->tab) {
            $types = $this->typeHandler->getTabTypes($this->tab);
            if ($types) {
                $builder->addBaseQueryParameter('type', $types);
            } else {
                $builder->addBaseQueryParameter('type', 'non existing type, this will fail');
            }

            if ('media' === $this->tab) {
                $builder->setDefaultDisplay('grid');
            }
        }

        return $types;
    }
}
