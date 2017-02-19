<?php

namespace MakinaCorpus\Ucms\Site\Portlet;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Drupal\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Drupal\Dashboard\Page\PageBuilder;
use MakinaCorpus\Drupal\Dashboard\Portlet\AbstractPortlet;
use MakinaCorpus\Ucms\Site\Access;

/**
 * Technical admin sites portlet
 */
class SitesPortlet extends AbstractPortlet
{
    use StringTranslationTrait;

    /**
     * @var DatasourceInterface
     */
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
     * {@inheritDoc}
     */
    public function getTitle()
    {
        return $this->t("All sites");
    }

    /**
     * {@inheritDoc}
     */
    public function getPath()
    {
        return 'admin/dashboard/site/all';
    }

    /**
     * {inheritDoc}
     */
    protected function createPage(PageBuilder $pageBuilder)
    {
        $pageBuilder
            ->setDatasource($this->datasource)
            ->setAllowedTemplates(['table' => 'module:ucms_site:Portlet/page-sites.html.twig'])
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function userIsAllowed(AccountInterface $account)
    {
        return $account->hasPermission(Access::PERM_SITE_MANAGE_ALL) || $account->hasPermission(Access::PERM_SITE_GOD);
    }
}
