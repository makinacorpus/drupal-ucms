<?php

namespace MakinaCorpus\Ucms\Seo\Page;

use Drupal\Core\Entity\EntityManager;
use MakinaCorpus\Ucms\Dashboard\Page\AbstractDisplay;
use MakinaCorpus\Ucms\Site\SiteManager;

class SiteAliasDisplay extends AbstractDisplay
{
    private $emptyMessage;
    private $entityManager;
    private $siteManager;

    /**
     * Default constructor
     */
    public function __construct(SiteManager $siteManager, EntityManager $entityManager, $emptyMessage = null)
    {
        $this->emptyMessage = $emptyMessage;
        $this->entityManager = $entityManager;
        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function displayAs($mode, $items)
    {
        $rows = [];
        $nodes = [];
        $sites = [];
        $types = \node_type_get_names();

        // Preload nodes
        foreach ($items as $item) {
            if ($item->node_id) {
                $nodes[$item->node_id] = $item->node_id;
                $sites[$item->site_id] = $item->site_id;
            }
        }
        if ($nodes) {
            $nodes = $this->entityManager->getStorage('node')->loadMultiple($nodes);
        }
        if ($sites) {
            $sites = $this->siteManager->getStorage()->loadAll($sites);
        }

        foreach ($items as $item) {

            $site = null;
            $siteLabel = '<em>' . $this->t("None") . '</em>';
            if ($item->site_id && isset($sites[$item->site_id])) {
                $site = $sites[$item->site_id];
                $siteLabel = l($site->title, 'admin/dashboard/site/' . $site->getId());
            }

            if ($item->node_id && isset($nodes[$item->node_id])) {
                $nodeLabel = $nodes[$item->node_id]->getTitle();
            } else {
                $nodeLabel = '<em>' . $this->t("None") . '</em>';
            }

            $realPath = 'node/'.$item->node_id;
            $realUrl = \url($realPath, ['ucms_site' => $site]);

            $rows[] = [
                \l('/'.$item->route, $realUrl),
                $nodeLabel,
                $types[$item->node_type] ?? '',
                $siteLabel,
                theme('ucms_dashboard_actions', ['actions' => $this->getActions($item), 'mode' => 'icon']),
            ];
        }

        return [
            '#prefix' => '<div class="col-md-12">', // FIXME should be in theme
            '#suffix' => '</div>',                  // FIXME should be in theme
            '#theme' => 'table',
            '#header' => [
                $this->t("Alias"),
                $this->t("Target"),
                $this->t("Type"),
                $this->t("Site"),
                '',
            ],
            '#empty' => $this->emptyMessage,
            '#rows' => $rows,
        ];
    }
}
