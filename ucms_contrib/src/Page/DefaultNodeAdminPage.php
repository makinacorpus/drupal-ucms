<?php

namespace MakinaCorpus\Ucms\Contrib\Page;

use Drupal\Core\Session\AccountInterface;
use MakinaCorpus\Drupal\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Drupal\Dashboard\Page\PageBuilder;
use MakinaCorpus\Ucms\Contrib\TypeHandler;
use MakinaCorpus\Ucms\Site\SiteManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default node admin page implementation, suitable for most use cases
 */
class DefaultNodeAdminPage implements NodeAdminPageInterface
{
    private $datasource;
    private $typeHandler;
    private $siteManager;
    private $tab;
    private $queryFilter;
    private $permission;
    private $siteContextCondition;

    /**
     * Default constructor
     *
     * @param DatasourceInterface $datasource
     * @param SiteManager $siteManager
     * @param \MakinaCorpus\Ucms\Contrib\TypeHandler $typeHandler
     * @param $permission
     * @param null $tab
     * @param mixed[] $queryFilter
     * @param null|boolean $inSiteContext
     *   Whether this admin page is visible or hidden in site context, if null, don't do anything
     * @internal param \string[] $types
     */
    public function __construct(
        DatasourceInterface $datasource,
        SiteManager $siteManager,
        TypeHandler $typeHandler,
        $permission,
        $tab = null,
        array $queryFilter = [],
        $inSiteContext = null
    ) {
        $this->datasource = $datasource;
        $this->typeHandler = $typeHandler;
        $this->siteManager = $siteManager;
        $this->permission = $permission;
        $this->tab = $tab;
        $this->queryFilter = $queryFilter;
        $this->siteContextCondition = $inSiteContext;
    }

    /**
     * {@inheritdoc}
     */
    public function userIsGranted(AccountInterface $account)
    {
        if (isset($this->siteContextCondition) && $this->siteManager->hasContext() !== $this->siteContextCondition) {
            return false;
        }

        return $account->hasPermission($this->permission);
    }

    /**
     * {@inheritdoc}
     */
    public function build(PageBuilder $builder, Request $request)
    {
        $builder
            ->setAllowedTemplates([
                'grid' => 'module:udashboard:views/Page/page-grid.html.twig',
                'table' => 'module:udashboard:views/Page/page.html.twig',
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
            $types = $this->typeHandler->getTabTypes($this->tab);
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
