<?php

namespace MakinaCorpus\Ucms\Tree\Page;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDisplay;
use MakinaCorpus\Ucms\Site\SiteManager;

class TreeAdminDisplay extends AbstractDisplay
{
    private $emptyMessage;
    private $siteManager;

    /**
     * Default constructor
     */
    public function __construct(SiteManager $siteManager, $emptyMessage = null)
    {
        $this->emptyMessage = $emptyMessage;
        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function displayAs($mode, $menus)
    {
        $rows = [];

        /** @var \MakinaCorpus\Umenu\Menu[] $menus */
        foreach ($menus as $menu) {

            // I could use some preload there...
            try {
                $siteName = check_plain($this->siteManager->getStorage()->findOne($menu->getSiteId())->getAdminTitle());
            } catch (\Exception $e) {
                // In theory, this can't happen...
                $siteName = '<em>' . $this->t("unknown") . '</em>';
            }

            $rows[] = [
                check_plain($menu->getId()),
                check_plain($menu->getName()),
                check_plain($menu->getTitle()),
                check_plain($menu->getDescription()),
                check_plain($siteName),
                theme('ucms_dashboard_actions', ['actions' => $this->getActions($menu), 'mode' => 'icon']),
            ];
        }

        return [
            '#prefix' => '<div class="col-md-12">', // FIXME should be in theme
            '#suffix' => '</div>', // FIXME should be in theme
            '#theme'  => 'table',
            '#header' => [
                $this->t("Id."),
                $this->t("Internal name"),
                $this->t("Title"),
                $this->t("Description"),
                $this->t("Site"),
                '',
            ],
            '#empty'  => $this->emptyMessage,
            '#rows'   => $rows,
        ];
    }
}
