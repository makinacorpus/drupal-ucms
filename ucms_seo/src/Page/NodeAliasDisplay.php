<?php

namespace MakinaCorpus\Ucms\Seo\Page;

use Drupal\Core\Entity\EntityManager;

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
            if ($item->site_id && $sites[$item->site_id]) {
                $site = $sites[$item->site_id];
                $siteLabel = l('admin/dashboard/site/' . $site->getId(), $site->title);
            }

            if (null === $item->language || 'und' === $item->language) {
                $language = '<em>' . $this->t("default") . '</em>';;
            } else {
                $language = check_plain($item->language);
            }

            $rows[] = [
                check_plain($item->alias),
                $siteLabel,
                $language,
                $item->is_canonical ? '<strong>' . $this->t("Yes") . '</strong>' : $this->t("No"),
                $item->expires ? format_date((new \DateTime($item->expires))->getTimestamp()) : $this->t("No"),
                $item->priority,
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
                $this->t("Language"),
                $this->t("Canonical"),
                $this->t("Priority"),
                $this->t("Expires"),
                '',
            ],
            '#empty'  => $this->emptyMessage,
            '#rows'   => $rows,
        ];
    }
}
