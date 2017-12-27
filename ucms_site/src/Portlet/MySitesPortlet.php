<?php

namespace MakinaCorpus\Ucms\Site\Portlet;

use MakinaCorpus\Calista\Action\Action;
use MakinaCorpus\Drupal\Calista\Portlet\AbstractPortlet;

class MySitesPortlet extends AbstractPortlet
{
    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        return $this->t("My sites");
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        return $this->renderPage('ucms_site.admin.datasource', '@ucms_site/views/Portlet/page-sites.html.twig', ['uid' => $GLOBALS['user']->uid]);
    }

    /**
     * {@inheritdoc}
     */
    public function getActions()
    {
        return [
            new Action($this->t("Request site"), 'admin/dashboard/site/request', null, 'globe', 0, true, true),
        ];
    }
}
