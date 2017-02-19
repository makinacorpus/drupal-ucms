<?php

namespace MakinaCorpus\Ucms\User\Portlet;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Drupal\Dashboard\Action\Action;
use MakinaCorpus\Drupal\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Drupal\Dashboard\Page\PageBuilder;
use MakinaCorpus\Drupal\Dashboard\Portlet\AbstractPortlet;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\User\UserAccess;

/**
 * User portlet display for user administrators.
 */
class UsersPortlet extends AbstractPortlet
{
    use StringTranslationTrait;

    private $siteManager;
    private $datasource;

    /**
     * Default constructor
     *
     * @param DatasourceInterface $datasource
     */
    public function __construct(DatasourceInterface $datasource, SiteManager $siteManager)
    {
        $this->datasource = $datasource;
        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        return $this->t("Users");
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return 'admin/dashboard/user';
    }

    /**
     * @return Action[]
     */
    public function getActions()
    {
        return [
            new Action($this->t("Create user"), 'admin/dashboard/user/add', null, 'user', 0, true, true),
        ];
    }

    /**
     * {inheritDoc}
     */
    protected function createPage(PageBuilder $pageBuilder)
    {
//         $pageState->setSortField('u.created');
//         $pageState->setSortOrder(PageState::SORT_DESC);
        $pageBuilder
            ->setDatasource($this->datasource)
            ->setAllowedTemplates(['table' => 'module:ucms_user:Portlet/page-users.html.twig'])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function userIsAllowed(AccountInterface $account)
    {
        return $account->hasPermission(UserAccess::PERM_MANAGE_ALL);
    }
}

