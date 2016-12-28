<?php

namespace MakinaCorpus\Ucms\Contrib\Page;

use Drupal\Core\Session\AccountInterface;

use MakinaCorpus\Ucms\Contrib\ContentTypeManager;
use MakinaCorpus\Ucms\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Ucms\Dashboard\Page\PageBuilder;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\HttpFoundation\Request;

/**
 * Default node admin page implementation, suitable for most use cases
 */
class DefaultNodeAdminPage implements NodeAdminPageInterface
{
    private $datasource;
    private $contentTypeManager;
    private $siteManager;
    private $tab;
    private $queryFilter;
    private $permission;

    /**
     * Default constructor
     *
     * @param DatasourceInterface $datasource
     * @param SiteManager $siteManager
     * @param string[] $types
     * @param mixed[] $queryFilter
     */
    public function __construct(
        DatasourceInterface $datasource,
        SiteManager $siteManager,
        ContentTypeManager $contentTypeManager,
        $permission,
        $tab = null,
        array $queryFilter = []
    ) {
        $this->datasource = $datasource;
        $this->contentTypeManager = $contentTypeManager;
        $this->siteManager = $siteManager;
        $this->permission = $permission;
        $this->tab = $tab;
        $this->queryFilter = $queryFilter;
    }

    /**
     * {@inheritdoc}
     */
    public function userIsGranted(AccountInterface $account)
    {
        return $account->hasPermission($this->permission);
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

        if ($this->queryFilter) {
            foreach ($this->queryFilter as $name => $value) {
                $builder->addBaseQueryParameter($name, $value);
            }
        }

        if ($this->siteManager->hasContext()) {
            $builder->addBaseQueryParameter('site_id', $this->siteManager->getContext()->getId());
        }

        if ($this->tab) {
            $types = $this->contentTypeManager->getTabTypes($this->tab);
            if ($types) {
                $builder->addBaseQueryParameter('type', $types);
            } else {
                $builder->addBaseQueryParameter('type', 'this will never work, sorry');
            }
        }

        if ('media' === $this->tab) {
            $builder->setDefaultDisplay('grid');
        }
    }
}
