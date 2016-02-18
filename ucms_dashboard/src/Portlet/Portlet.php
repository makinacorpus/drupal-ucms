<?php

namespace MakinaCorpus\Ucms\Dashboard\Portlet;


/**
 * Class DashboardPortlet
 * @package MakinaCorpus\Ucms\Dashboard\Dashboard
 */
abstract class Portlet implements PortletInterface
{
    /**
     * @return array Render array usable in twig
     */
    public function renderActions()
    {
        return [
            '#theme' => 'ucms_dashboard_actions',
            '#actions' => $this->getActions(),
        ];
    }
}
