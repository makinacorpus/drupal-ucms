<?php

namespace MakinaCorpus\Ucms\Dashboard\Dashboard;


/**
 * Class DashboardPortlet
 * @package MakinaCorpus\Ucms\Dashboard\Dashboard
 */
abstract class DashboardPortlet implements DashboardPortletInterface
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
