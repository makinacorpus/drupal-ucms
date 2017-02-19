<?php

namespace MakinaCorpus\Ucms\Contrib\Portlet;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Drupal\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Drupal\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Drupal\Dashboard\Page\PageBuilder;
use MakinaCorpus\Drupal\Dashboard\Page\PageState;
use MakinaCorpus\Drupal\Dashboard\Portlet\AbstractPortlet;
use MakinaCorpus\Ucms\Contrib\TypeHandler;
use MakinaCorpus\Ucms\Site\SiteManager;

class MediaPortlet extends AbstractPortlet
{
    use StringTranslationTrait;

    /**
     * @var DatasourceInterface
     */
    private $datasource;

    /**
     * @var ActionProviderInterface
     */
    private $actionProvider;

    /**
     * @var TypeHandler
     */
    private $typeHandler;

    /**
     * @var SiteManager
     */
    private $siteManager;

    /**
     * Default constructor
     *
     * @param DatasourceInterface $datasource
     * @param ActionProviderInterface $actionProvider
     * @param TypeHandler $typeHandler
     * @param ActionProviderInterface $actionProvider
     * @param TypeHandler $typeHandler
     */
    public function __construct(
        DatasourceInterface $datasource,
        ActionProviderInterface $actionProvider,
        TypeHandler $typeHandler,
        SiteManager $siteManager
    ) {
        $this->datasource = $datasource;
        $this->actionProvider = $actionProvider;
        $this->typeHandler = $typeHandler;
        $this->siteManager = $siteManager;
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
        if ($this->siteManager->getAccess()->userIsWebmaster($this->getAccount())) {
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
    protected function createPage(PageBuilder $pageBuilder)
    {
        $query = [];
        $query['type'] = $this->typeHandler->getMediaTypes();
        $query['is_global'] = 0;

        $currentUser = $this->getAccount();

        // Only for webmaster, show local nodes (instead of own).
        if ($this->siteManager->getAccess()->userIsWebmaster($currentUser)) {
            $map = $this->siteManager->getAccess()->getUserRoles($currentUser);
            $site_ids = [];
            foreach ($map as $item) {
                $site_ids[] = $item->getSiteId();
            }

            if ($site_ids) {
                $query['site_id'] = $site_ids;
            }
        }

        $pageBuilder
            ->setDatasource($this->datasource)
            ->setAllowedTemplates(['table' => 'module:ucms_contrib:Portlet/page-node-portlet.html.twig'])
            ->setBaseQuery($query)
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function userIsAllowed(AccountInterface $account)
    {
        return true;
    }
}
