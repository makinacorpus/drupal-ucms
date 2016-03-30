<?php

namespace MakinaCorpus\Ucms\Contrib\Portlet;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Contrib\TypeHandler;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Dashboard\Portlet\AbstractAdminPortlet;
use MakinaCorpus\Ucms\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;

class MediaPortlet extends AbstractAdminPortlet
{
    use StringTranslationTrait;

    /**
     * @var ActionProviderInterface
     */
    private $actionProvider;

    /**
     * @var TypeHandler
     */
    private $typeHandler;

    /**
     * Default constructor
     *
     * @param DatasourceInterface $datasource
     * @param ActionProviderInterface $actionProvider
     * @param TypeHandler $typeHandler
     */
    public function __construct(
        DatasourceInterface $datasource,
        ActionProviderInterface $actionProvider,
        TypeHandler $typeHandler
    ) {
        parent::__construct($datasource);

        $this->actionProvider = $actionProvider;
        $this->typeHandler = $typeHandler;
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
        $query['type'] = $this->typeHandler->getMediaTypes();
        $query['owner'] = $this->getAccount()->id();

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
