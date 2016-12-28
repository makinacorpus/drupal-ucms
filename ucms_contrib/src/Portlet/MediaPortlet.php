<?php

namespace MakinaCorpus\Ucms\Contrib\Portlet;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Contrib\ContentTypeManager;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Dashboard\Portlet\AbstractAdminPortlet;
use MakinaCorpus\Ucms\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Site\SiteManager;

class MediaPortlet extends AbstractAdminPortlet
{
    use StringTranslationTrait;

    /**
     * @var ActionProviderInterface
     */
    private $actionProvider;

    /**
     * @var ContentTypeManager
     */
    private $contentTypeManager;

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
     * @param ContentTypeManager $contentTypeManager
     * @param ActionProviderInterface $actionProvider
     * @param \MakinaCorpus\Ucms\Contrib\ContentTypeManager $contentTypeManager
     */
    public function __construct(
        DatasourceInterface $datasource,
        ActionProviderInterface $actionProvider,
        ContentTypeManager $contentTypeManager,
        SiteManager $siteManager,
        AccountInterface $currentUser
    ) {
        parent::__construct($datasource);

        $this->actionProvider = $actionProvider;
        $this->contentTypeManager = $contentTypeManager;
        $this->siteManager = $siteManager;
        $this->currentUser = $currentUser;
    }

    /**
     * {@inheritDoc}
     */
    public function getTitle()
    {
        return $this->t("Media");
    }

    /**
     * {@inheritDoc}
     */
    public function getPath()
    {
        if ($this->siteManager->getAccess()->userIsWebmaster($this->currentUser)) {
            return 'admin/dashboard/media/local';
        }
        return 'admin/dashboard/media';
    }

    /**
     * {@inheritDoc}
     */
    public function getActions()
    {
        return $this->actionProvider->getActions('media');
    }

    /**
     * {@inheritDoc}
     */
    public function renderActions()
    {
        $build = parent::renderActions();
        $build['#title'] = $this->t("Create media");

        return $build;
    }

    /**
     * {@inheritDoc}
     */
    protected function getDisplay(&$query, PageState $pageState)
    {
        $query['type'] = $this->contentTypeManager->getMediaTypes();
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

        return new NodePortletDisplay($this->t("You have no media yet."));
    }

    /**
     * {@inheritDoc}
     */
    public function userIsAllowed(AccountInterface $account)
    {
        return true;
    }
}
