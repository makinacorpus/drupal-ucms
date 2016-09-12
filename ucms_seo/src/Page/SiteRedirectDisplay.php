<?php

namespace MakinaCorpus\Ucms\Seo\Page;

use Drupal\Core\Entity\EntityManager;
use MakinaCorpus\Ucms\Dashboard\Page\AbstractDisplay;
use MakinaCorpus\Ucms\Site\SiteManager;

class SiteRedirectDisplay extends AbstractDisplay
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
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Default constructor
     *
     * @param SiteManager $siteManager
     * @param EntityManager $entityManager
     * @param string $emptyMessage
     */
    public function __construct(SiteManager $siteManager, EntityManager $entityManager, $emptyMessage = null)
    {
        $this->siteManager = $siteManager;
        $this->entityManager = $entityManager;
        $this->emptyMessage = $emptyMessage;
    }

    /**
     * {@inheritdoc}
     */
    protected function displayAs($mode, $items)
    {
        $rows = [];
        $nodes = [];

        // Preload nodes
        foreach ($items as $item) {
            $nodes[$item->nid] = $item->nid;
        }
        if ($nodes) {
            $nodes = $this->entityManager->getStorage('node')->loadMultiple($nodes);
        }

        foreach ($items as $item) {
            if (isset($nodes[$item->nid])) {
                $nodeLabel = $nodes[$item->nid]->getTitle();
            }
            else {
                $nodeLabel = $this->t("None");
            }

            $rows[] = [
                check_plain($item->path),
                isset($nodes[$item->nid]) ? l($nodeLabel, 'node/'.$item->nid) : $nodeLabel,
                theme('ucms_dashboard_actions', ['actions' => $this->getActions($item), 'mode' => 'icon']),
            ];
        }

        return [
            '#prefix' => '<div class="col-md-12">', // FIXME should be in theme
            '#suffix' => '</div>',                  // FIXME should be in theme
            '#theme'  => 'table',
            '#header' => [
                $this->t("Path"),
                $this->t("Content"),
                '',
            ],
            '#empty'  => $this->emptyMessage,
            '#rows'   => $rows,
        ];
    }
}
