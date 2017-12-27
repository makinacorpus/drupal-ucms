<?php

namespace MakinaCorpus\Ucms\Site\Portlet;

use MakinaCorpus\Drupal\Calista\Portlet\AbstractPortlet;
use MakinaCorpus\Ucms\Site\Access;

class SitesPortlet extends AbstractPortlet
{
    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        return $this->t("All sites");
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        return $this->renderPage('ucms_site.admin.datasource', '@ucms_site/Portlet/page-sites.html.twig');
    }

    /**
     * {@inheritdoc}
     */
    public function isGranted()
    {
        return $this->authorizationChecker->isGranted([Access::PERM_SITE_MANAGE_ALL, Access::PERM_SITE_GOD]);
    }
}
