<?php

namespace MakinaCorpus\Ucms\Seo\Page;

use Drupal\Core\Entity\EntityManager;
use MakinaCorpus\Ucms\Dashboard\Page\AbstractDisplay;
use MakinaCorpus\Ucms\Site\SiteManager;

class RedirectDisplay extends AbstractDisplay
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
        $sites = [];
        $types = \node_type_get_names();

        // Preload sites
        /** @var \MakinaCorpus\Ucms\Seo\Path\Redirect $item */
        foreach ($items as $item) {
            if ($siteId = ($item->getSiteId() ?? null)) {
                $sites[$siteId] = $siteId;
            }
        }
        if ($sites) {
            $sites = $this->siteManager->getStorage()->loadAll($sites);
        }

        foreach ($items as $item) {
            $siteId = $item->getSiteId();
            $nodeId = $item->getNodeId();

            $site = null;
            $siteLabel = '<em>' . $this->t("None") . '</em>';
            if ($site = ($sites[$siteId] ?? null)) {
                $siteLabel = l($site->getAdminTitle(), 'admin/dashboard/site/'.$site->getId());
            }

            $realPath = 'node/'.$nodeId;
            $expires = t("Never");
            if ($item->hasExpiryDate()) {
                $expires = \format_date($item->expiresAt()->getTimestamp());
            }

            $rows[] = [
                \check_plain($item->getPath()),
                \l($item->getNodeTitle() ?? $realPath, $realPath, ['ucms_site' => $site]),
                $types[$item->getNodeType()] ?? '',
                $siteLabel,
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
                $this->t("Site"),
                $this->t("Expires"),
                '',
            ],
            '#empty'  => $this->emptyMessage,
            '#rows'   => $rows,
        ];
    }
}
