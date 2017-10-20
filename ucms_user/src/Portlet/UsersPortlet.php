<?php


namespace MakinaCorpus\Ucms\User\Portlet;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Dashboard\Portlet\AbstractAdminPortlet;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\User\UserAccess;


class UsersPortlet extends AbstractAdminPortlet
{
    use StringTranslationTrait;


    /**
     * @var SiteManager
     */
    private $siteManager;


    /**
     * Default constructor
     *
     * @param DatasourceInterface $datasource
     */
    public function __construct(DatasourceInterface $datasource, SiteManager $siteManager)
    {
        parent::__construct($datasource);
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
     * {@inheritdoc}
     */
    protected function getDisplay(&$query, PageState $pageState)
    {
        $pageState->setSortField('u.created');
        $pageState->setSortOrder(PageState::SORT_DESC);
        return new UsersPortletDisplay($this->siteManager, $this->t("No user created yet."));
    }


    /**
     * {@inheritdoc}
     */
    public function userIsAllowed(AccountInterface $account)
    {
        return $account->hasPermission(UserAccess::PERM_MANAGE_ALL) || $this->account->hasPermission(UserAccess::PERM_USER_GOD);
    }
}

