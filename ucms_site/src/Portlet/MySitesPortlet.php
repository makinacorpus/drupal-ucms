<?php

namespace MakinaCorpus\Ucms\Site\Portlet;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Drupal\Dashboard\Action\Action;
use MakinaCorpus\Drupal\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Drupal\Dashboard\Page\PageBuilder;
use MakinaCorpus\Drupal\Dashboard\Portlet\AbstractPortlet;
use MakinaCorpus\Ucms\Site\Access;

/**
 * Current user site's portlet
 */
class MySitesPortlet extends AbstractPortlet
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
     * Return the title of this portlet.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->t("My sites");
    }

    /**
     * Return the path for the main page of this portlet.
     *
     * @return null|string
     */
    public function getPath()
    {
        return 'admin/dashboard/site/mine';
    }

    /**
     * @return Action[]
     */
    public function getActions()
    {
        return [
            new Action($this->t("Request site"), 'admin/dashboard/site/request', null, 'globe', 0, true, true),
        ];
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
     * Return true if portlet if visible for user.
     *
     * @param $account
     * @return mixed
     */
    public function userIsAllowed(AccountInterface $account)
    {
        return $account->hasPermission(Access::PERM_SITE_REQUEST);
    }
}
