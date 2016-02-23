<?php

namespace MakinaCorpus\Ucms\Contrib\Portlet;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Dashboard\Portlet\AbstractPortlet;

class MediaPortlet extends AbstractPortlet
{
    use StringTranslationTrait;

    /**
     * @var ActionProviderInterface
     */
    private $actionProvider;

    public function __construct(ActionProviderInterface $actionProvider)
    {
        $this->actionProvider = $actionProvider;
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
    public function getContent()
    {
        return '@todo';
    }

    /**
     * {@inheritDoc}
     */
    public function userIsAllowed(AccountInterface $account)
    {
        return true;
    }
}
