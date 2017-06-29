<?php

namespace MakinaCorpus\Ucms\Contrib\Page;

use Drupal\Core\Session\AccountInterface;
use MakinaCorpus\Calista\Datasource\DatasourceInterface;
use MakinaCorpus\Ucms\Contrib\TypeHandler;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Calista\DependencyInjection\AbstractPageDefinition;
use MakinaCorpus\Calista\View\Html\TwigView;
use MakinaCorpus\Calista\Datasource\InputDefinition;

/**
 * Default node admin page implementation, suitable for most use cases
 */
class DefaultNodeAdminPage extends AbstractPageDefinition implements NodeAdminPageInterface
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
     * @param TypeHandler $typeHandler
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
    public function getDatasource()
    {
        return $this->datasource;
    }

    /**
     * {@inheritdoc}
     */
    public function getInputDefinition(array $options = [])
    {
        return new InputDefinition($this->getDatasource(), [
            'base_query'          => $this->queryFilter,
            'limit_default'       => 24,
            'pager_enable'        => true,
            'pager_param'         => 'page',
            'search_enable'       => true,
            'search_param'        => 's',
            'sort_default_field'  => 'updated',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDisplayOptions()
    {
        return [
            'view_type' => TwigView::class,
            'templates' => [
                'table' => '@calista/Page/page.html.twig',
                'grid'  => '@calista/Page/page-grid.html.twig',
            ],
        ];
    }
}
