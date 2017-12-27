<?php

namespace MakinaCorpus\Ucms\Contrib\Portlet;

use MakinaCorpus\Drupal\Calista\Portlet\AbstractPortlet;
use MakinaCorpus\Ucms\Contrib\TypeHandler;

class MediaPortlet extends AbstractPortlet
{
    private $typeHandler;

    /**
     * Default constructor
     *
     * @param TypeHandler $typeHandler
     */
    public function __construct(TypeHandler $typeHandler)
    {
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
    public function getRoute()
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
     * {@inheritdoc}
     */
    public function getContent()
    {
        return $this->renderPage(
            'ucms_contrib.datasource.node',
            '@ucms_contrib/views/Portlet/page-node-portlet.html.twig',
            [
                'type' => $this->typeHandler->getMediaTypes(),
            ]
        );
    }
}
