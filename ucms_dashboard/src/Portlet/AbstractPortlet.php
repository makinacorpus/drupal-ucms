<?php

namespace MakinaCorpus\Ucms\Dashboard\Portlet;

abstract class AbstractPortlet implements PortletInterface
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
