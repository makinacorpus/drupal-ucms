<?php

namespace MakinaCorpus\Ucms\Seo\Page;

use Drupal\Core\Entity\EntityManager;
use MakinaCorpus\Ucms\Dashboard\Page\AbstractDisplay;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\Site;

class SiteRedirectDisplay extends AbstractDisplay
{
    private $emptyMessage;
    private $entityManager;
    private $site;
    private $siteManager;

    /**
     * Default constructor
     */
    public function __construct(SiteManager $siteManager, EntityManager $entityManager, Site $site, $emptyMessage = null)
    {
        $this->emptyMessage = $emptyMessage;
        $this->entityManager = $entityManager;
        $this->site = $site;
        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function displayAs($mode, $items)
    {
        $rows = [];
        $nodes = [];
        $types = \node_type_get_names();

        // Preload nodes
        foreach ($items as $item) {
            $nodes[$item->nid] = $item->nid;
        }
        if ($nodes) {
            $nodes = $this->entityManager->getStorage('node')->loadMultiple($nodes);
        }

        foreach ($items as $item) {

            $realPath = 'node/'.$item->nid;
            $expires = t("Never");
            if ($item->expires) {
                if ($date = new \DateTimeImmutable()) {
                    $expires = \format_date($date->getTimestamp());
                }
            }

            $rows[] = [
                check_plain($item->path),
                \l($item->node_title ?? $realPath, $realPath, ['ucms_site' => $this->site]),
                $types[$item->node_type] ?? '',
                $expires,
                theme('ucms_dashboard_actions', ['actions' => $this->getActions($item), 'mode' => 'icon']),
            ];
        }

        return [
            '#prefix' => '<div class="col-md-12">', // FIXME should be in theme
            '#suffix' => '</div>',                  // FIXME should be in theme
            '#theme'  => 'table',
            '#header' => [
                $this->t("Old path"),
                $this->t("Target"),
                $this->t("Type"),
                $this->t("Expires"),
                '',
            ],
            '#empty'  => $this->emptyMessage,
            '#rows'   => $rows,
        ];
    }
}
