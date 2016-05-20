<?php

namespace MakinaCorpus\Ucms\Seo\Page;

use Drupal\Core\Entity\EntityManager;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDisplay;
use MakinaCorpus\Ucms\Site\SiteManager;

class SiteAliasDisplay extends AbstractDisplay
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
        $nodes  = [];

        // Preload nodes
        foreach ($items as $item) {
            if ($item->node_id) {
                $nodes[$item->node_id] = $item->node_id;
            }
        }
        if ($nodes) {
            $nodes = $this->entityManager->getStorage('node')->loadMultiple($nodes);
        }

        foreach ($items as $item) {

            if ($item->node_id && isset($nodes[$item->node_id])) {
                $nodeLabel = $nodes[$item->node_id]->getTitle();
            } else {
                $nodeLabel = '<em>' . $this->t("None") . '</em>';
            }

            if (null === $item->language || 'und' === $item->language) {
                $language = '<em>' . $this->t("default") . '</em>';;
            } else {
                $language = check_plain($language);
            }

            $rows[] = [
                check_plain($item->alias),
                $nodeLabel,
                $language,
                $item->is_canonical ? '<strong>' . $this->t("Yes") . '</strong>' : $this->t("No"),
                $item->priority,
                $item->expires ? format_date($item->expires) : $this->t("No"),
                theme('ucms_dashboard_actions', ['actions' => $this->getActions($item), 'mode' => 'icon']),
            ];
        }

        return [
            '#prefix' => '<div class="col-md-12">', // FIXME should be in theme
            '#suffix' => '</div>',                  // FIXME should be in theme
            '#theme'  => 'table',
            '#header' => [
                $this->t("Alias"),
                $this->t("Content"),
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
