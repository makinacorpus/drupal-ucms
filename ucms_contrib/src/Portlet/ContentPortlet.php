<?php

namespace MakinaCorpus\Ucms\Contrib\Portlet;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Dashboard\Portlet\Portlet;

/**
 * Class ContentPortlet
 * @package MakinaCorpus\Ucms\Contrib\Portlet
 */
class ContentPortlet extends Portlet
{
    use StringTranslationTrait;

    /**
     * @var ActionProviderInterface
     */
    private $actionProvider;

    /**
     * ContentPortlet constructor.
     * @param ActionProviderInterface $actionProvider
     */
    public function __construct(ActionProviderInterface $actionProvider)
    {
        $this->actionProvider = $actionProvider;
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
        return 'admin/dashboard/content';
    }

    /**
     * {@inheritDoc}
     */
    public function getActions()
    {
        return $this->actionProvider->getActions('content');
    }

    /**
     * {@inheritDoc}
     */
    public function renderActions()
    {
        $build = parent::renderActions();
        $build['#title'] = $this->t("Create content");

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
    public function userIsAllowed(\stdClass $account)
    {
        return true;
    }
}
