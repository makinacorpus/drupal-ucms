<?php

namespace MakinaCorpus\Ucms\Contrib\Portlet;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Contrib\TypeHandler;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Dashboard\Portlet\AbstractAdminPortlet;
use MakinaCorpus\Ucms\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Site\SiteManager;

class ContentPortlet extends AbstractAdminPortlet
{
    use StringTranslationTrait;

    /**
     * @var ActionProviderInterface
     */
    private $actionProvider;

    /**
     * @var \MakinaCorpus\Ucms\Contrib\TypeHandler
     */
    private $typeHandler;

    /**
     * @var \MakinaCorpus\Ucms\Site\SiteManager
     */
    private $siteManager;

    /**
     * @var \Drupal\Core\Session\AccountInterface
     */
    private $currentUser;

    /**
     * Default constructor
     *
     * @param DatasourceInterface $datasource
     * @param ActionProviderInterface $actionProvider
     * @param \MakinaCorpus\Ucms\Contrib\TypeHandler $typeHandler

     */
    public function __construct(
        DatasourceInterface $datasource,
        ActionProviderInterface $actionProvider,
        TypeHandler $typeHandler,
        SiteManager $siteManager,
        AccountInterface $currentUser
    ) {
        parent::__construct($datasource);

        $this->actionProvider = $actionProvider;
        $this->typeHandler = $typeHandler;
        $this->siteManager = $siteManager;
        $this->currentUser = $currentUser;
    }

    /**
     * {@inheritDoc}
     */
    public function getTitle()
    {
        return $this->t("Content");
    }

    /**
     * {@inheritDoc}
     */
    public function getPath()
    {
        if ($this->siteManager->getAccess()->userIsWebmaster($this->currentUser)) {
            return 'admin/dashboard/content/local';
        }
        return 'admin/dashboard/content';
    }

    /**
     * {@inheritDoc}
     */
    public function getActions()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function renderActions()
    {
        $build = [];

        // FIXME, allow to have multiple action groups
        $build[TypeHandler::TYPE_EDITORIAL] = [
          '#theme'      => 'ucms_dashboard_actions',
          '#actions'    => $this->actionProvider->getActions(TypeHandler::TYPE_EDITORIAL),
          '#show_title' => true,
          '#title'      => $this->t("Create content"),
        ];
        $build[TypeHandler::TYPE_COMPONENT] = [
          '#theme'      => 'ucms_dashboard_actions',
          '#actions'    => $this->actionProvider->getActions(TypeHandler::TYPE_COMPONENT),
          '#show_title' => true,
          '#title'      => $this->t("Create component"),
        ];

        return $build;
    }

    /**
     * {@inheritDoc}
     */
    protected function getDisplay(&$query, PageState $pageState)
    {
        $query['type'] = $this->typeHandler->getEditorialContentTypes();
        $query['is_global'] = 0;

        // Only for webmaster, show local nodes (instead of own).
        if ($this->siteManager->getAccess()->userIsWebmaster($this->currentUser)) {
            $map = $this->siteManager->getAccess()->getUserRoles($this->currentUser);
            $site_ids = [];
            foreach ($map as $item) {
                $site_ids[] = $item->getSiteId();
            }

            if ($site_ids) {
                $query['site_id'] = $site_ids;
            }
        }

        $pageState->setSortField('created');

        return new NodePortletDisplay($this->t("You have no content yet."));
    }

    /**
     * {@inheritDoc}
     */
    public function userIsAllowed(AccountInterface $account)
    {
        return $account->hasPermission('access ucms content overview'); // @todo const
    }
}
