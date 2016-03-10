<?php


namespace MakinaCorpus\Ucms\Layout\EventDispatcher;

use Drupal\Core\Entity\EntityManager;
use Drupal\user\UserInterface;

use MakinaCorpus\Ucms\Layout\ContextManager;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;


class SiteEventListener
{
    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * @var ContextManager
     */
    private $contextManager;

    /**
     * @var SiteManager
     */
    private $siteManager;

    /**
     * @var RequestStack
     */
    private $requestStack;


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
        RequestStack $requestStack
    ) {
        $this->db = $db;
        $this->contextManager = $contextManager;
        $this->siteManager = $siteManager;
        $this->requestStack = $requestStack;
    }


    public function onSiteInit(SiteEvent $event)
    {
        // @todo Ugly... The best would be to not use drupal_valid_token()
        require_once DRUPAL_ROOT . '/includes/common.inc';

        $request = $this->requestStack->getCurrentRequest();
        $token = null;

        if (preg_match('/^node\/([0-9]+)$/', $request->get('q'), $matches) === 1) {
            if (($token = $request->get('edit')) && drupal_valid_token($token)) {
                $this->contextManager->getPageContext()->setToken($token);
            }
            elseif (($token = $request->get('site_edit')) && drupal_valid_token($token)) {
                $this->contextManager->getTransversalContext()->setToken($token);
            }

            $this->contextManager->getPageContext()->setCurrentLayoutNodeId((int) $matches[1]);

            $site = $event->getSite();
            $this->contextManager->getTransversalContext()->setCurrentLayoutNodeId($site->home_nid);
        }

        if (($token = $request->get('token')) && drupal_valid_token($token) && ($region = $request->get('region'))) {
            $site = $event->getSite();

            if ($this->contextManager->isPageContextRegion($region, $site->theme)) {
                $this->contextManager->getPageContext()->setToken($token);
            }
            elseif ($this->contextManager->isTransversalContextRegion($region, $site->theme)) {
                $this->contextManager->getTransversalContext()->setToken($token);
            }
        }
    }


    public function onSiteClone(GenericEvent $event)
    {
        /* @var Site */
        $source = $event->getArgument('source');
        /* @var Site */
        $target = $event->getSubject();

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
                    usn.nid = usn.nid
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
            );

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
                    target_ul.nid = uld.nid
                    AND target_ul.site_id = :target
                WHERE
                    (n.status = 1 OR n.is_global = 0)
            ",
                [
                    ':source' => $source->getId(),
                    ':target' => $target->getId(),
                ]
            );
    }
}
