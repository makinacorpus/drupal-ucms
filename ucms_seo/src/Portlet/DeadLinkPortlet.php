<?php

namespace MakinaCorpus\Ucms\Seo\Portlet;

use MakinaCorpus\Drupal\Calista\Portlet\AbstractPortlet;

class DeadLinkPortlet extends AbstractPortlet
{
    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        return $this->t("Dead links");
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        return $this->renderPage('ucms_seo.deadlinks.datasource', '@ucms_seo/views/Portlet/page-deadlink.html.twig');
    }
}
