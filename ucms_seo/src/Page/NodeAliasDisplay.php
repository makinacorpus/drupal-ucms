<?php

namespace MakinaCorpus\Ucms\Seo\Page;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDisplay;
use MakinaCorpus\Ucms\Site\SiteManager;

class NodeAliasDisplay extends AbstractDisplay
{
    /**
     * @var string
     */
    private $emptyMessage;

    /**
     * @var SiteManager
     */
    private $siteManager;

    /**
     * Default constructor
     *
     * @param SiteManager $siteManager
     * @param string $emptyMessage
     */
    public function __construct(SiteManager $siteManager, $emptyMessage = null)
    {
        $this->siteManager = $siteManager;
        $this->emptyMessage = $emptyMessage;
    }

    /**
     * {@inheritdoc}
     */
    protected function displayAs($mode, $items)
    {
        $rows   = [];
        $sites  = [];

        foreach ($items as $item) {
            if ($item->site_id) {
                $sites[$item->site_id] = $item->site_id;
            }
        }
        if (!empty($sites)) {
            $sites = $this->siteManager->getStorage()->loadAll($sites);
        }

        foreach ($items as $item) {

            $siteLabel = '<em>' . $this->t("None") . '</em>';
            if ($item->site_id && $sites[$item->site_id]) {
                $site = $sites[$item->site_id];
                $siteLabel = l('admin/dashboard/site/' . $site->getId(), $site->title);
            }

            $rows[] = [
                check_plain($item->alias),
                $siteLabel,
                // @todo langauge $item->language
                $item->is_canonical ? $this->t("Yes") : '',
                $item->expires ? format_date($item->expires) : '',
                theme('ucms_dashboard_actions', ['actions' => $this->getActions($item), 'mode' => 'icon']),
            ];
        }

        return [
            '#prefix' => '<div class="col-md-12">', // FIXME should be in theme
            '#suffix' => '</div>',                  // FIXME should be in theme
            '#theme'  => 'table',
            '#header' => [
                $this->t("Alias"),
                $this->t("Site"),
                //$this->t("Language"),
                $this->t("Canonical"),
                $this->t("Expires"),
                '',
            ],
            '#empty'  => $this->emptyMessage,
            '#rows'   => $rows,
        ];
    }
}
