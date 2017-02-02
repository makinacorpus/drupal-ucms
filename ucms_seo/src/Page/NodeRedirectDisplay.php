<?php

namespace MakinaCorpus\Ucms\Seo\Page;

use Drupal\Core\Entity\EntityManager;

use MakinaCorpus\Drupal\Dashboard\Page\AbstractDisplay;
use MakinaCorpus\Ucms\Site\SiteManager;

class NodeRedirectDisplay extends AbstractDisplay
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
        $rows   = [];
        $sites  = [];

        // Preload sites
        foreach ($items as $item) {
            if ($item->site_id) {
                $sites[$item->site_id] = $item->site_id;
            }
        }
        if ($sites) {
            $sites = $this->siteManager->getStorage()->loadAll($sites);
        }

        foreach ($items as $item) {

            $siteLabel = '<em>' . $this->t("None") . '</em>';
            if ($item->site_id && isset($sites[$item->site_id])) {
                $site = $sites[$item->site_id];
                $siteLabel = l($site->title, 'admin/dashboard/site/' . $site->getId());
            }

            $rows[] = [
                check_plain($item->path),
                $siteLabel,
                theme('udashboard_actions', ['actions' => $this->getActions($item)]),
            ];
        }

        return [
            '#prefix' => '<div class="col-md-12">', // FIXME should be in theme
            '#suffix' => '</div>',                  // FIXME should be in theme
            '#theme'  => 'table',
            '#header' => [
                $this->t("Old path"),
                $this->t("Site"),
                '',
            ],
            '#empty'  => $this->emptyMessage,
            '#rows'   => $rows,
        ];
    }
}
