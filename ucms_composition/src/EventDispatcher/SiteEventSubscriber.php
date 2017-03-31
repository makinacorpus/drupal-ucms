<?php

namespace MakinaCorpus\Ucms\Layout\EventDispatcher;

use Drupal\Core\Entity\EntityManager;

use MakinaCorpus\Ucms\Layout\ContextManager;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteCloneEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvents;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class SiteEventSubscriber implements EventSubscriberInterface
{
    private $db;
    private $contextManager;
    private $siteManager;
    private $entityManager;
    private $requestStack;

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            SiteEvents::EVENT_POST_INIT => [
                ['onSitePostInit', 0]
            ],
            SiteEvents::EVENT_CLONE => [
                ['onSiteClone', 0]
            ],
        ];
    }

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param ContextManager $contextManager
     * @param SiteManager $siteManager
     * @param RequestStack $requestStack
     */
    public function __construct(
        \DatabaseConnection $db,
        ContextManager $contextManager,
        SiteManager $siteManager,
        EntityManager $entityManager,
        RequestStack $requestStack
    ) {
        $this->db = $db;
        $this->contextManager = $contextManager;
        $this->siteManager = $siteManager;
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    /**
     * Home page handling mostly.
     */
    public function onSitePostInit(SiteEvent $event)
    {
        // @todo Ugly... The best would be to not use drupal_valid_token()
        require_once DRUPAL_ROOT . '/includes/common.inc';

        $request      = $this->requestStack->getCurrentRequest();
        $pageContext  = $this->contextManager->getPageContext();
        $transContext = $this->contextManager->getSiteContext();
        $site         = $event->getSite();
        $token        = null;
        $matches      = [];

        // Column is nullable, so this is possible
        if ($siteHomeNid = $site->getHomeNodeId()) {
            if (($token = $request->get(ContextManager::PARAM_SITE_TOKEN)) && drupal_valid_token($token)) {
                $transContext->setToken($token);
            }
            $transContext->setCurrentLayoutNodeId($siteHomeNid, $site->getId());
        }

        // @todo $_GET['q']: cannot use Request::get() here since Drupal
        // alters the 'q' variable directly in the $_GET array
        if (preg_match('/^node\/([0-9]+)$/', $_GET['q'], $matches) === 1) {
            if (($token = $request->get(ContextManager::PARAM_PAGE_TOKEN)) && drupal_valid_token($token)) {
                $pageContext->setToken($token);
            }
            $pageContext->setCurrentLayoutNodeId((int)$matches[1], $site->getId());
        }

        if (($token = $request->get(ContextManager::PARAM_AJAX_TOKEN)) && drupal_valid_token($token) && ($region = $request->get('region'))) {
            if ($this->contextManager->isPageContextRegion($region, $site->theme)) {
                $pageContext->setToken($token);
            } else if ($this->contextManager->isTransversalContextRegion($region, $site->theme)) {
                $transContext->setToken($token);
            }
        }
    }

    /**
     * When cloning a site, we need to clone all layouts as well.
     */
    public function onSiteClone(SiteCloneEvent $event)
    {
        $source = $event->getTemplateSite();
        $target = $event->getSite();

        // First copy node layouts
        $this
            ->db
            ->query(
                "
                INSERT INTO {ucms_layout} (site_id, nid)
                SELECT
                    :target, usn.nid
                FROM {ucms_layout} ul
                JOIN {ucms_site_node} usn ON
                    ul.nid = usn.nid
                    AND usn.site_id = :target
                WHERE
                    ul.site_id = :source
                    AND NOT EXISTS (
                        SELECT 1
                        FROM {ucms_layout} s_ul
                        WHERE
                            s_ul.nid = ul.nid
                            AND s_ul.site_id = :target3
                    )
            ",
                [
                    ':target'  => $target->getId(),
                    ':target2' => $target->getId(),
                    ':source'  => $source->getId(),
                    ':target3' => $target->getId(),
                ]
            )
        ;

        // Then duplicate layout data
        $this
            ->db
            ->query(
                "
                INSERT INTO {ucms_layout_data}
                    (layout_id, region, nid, weight, view_mode)
                SELECT
                    target_ul.id,
                    uld.region,
                    uld.nid,
                    uld.weight,
                    uld.view_mode
                FROM {ucms_layout} source_ul
                JOIN {ucms_layout_data} uld ON
                    source_ul.id = uld.layout_id
                    AND source_ul.site_id = :source
                JOIN {node} n ON n.nid = uld.nid
                JOIN {ucms_layout} target_ul ON
                    target_ul.nid = source_ul.nid
                    AND target_ul.site_id = :target
                WHERE
                    (n.status = 1 OR n.is_global = 0)
            ",
                [
                    ':source' => $source->getId(),
                    ':target' => $target->getId(),
                ]
            )
        ;
    }
}
