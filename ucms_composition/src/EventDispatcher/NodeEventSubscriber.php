<?php

namespace MakinaCorpus\Ucms\Composition\EventDispatcher;

use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeEvent;
use MakinaCorpus\Layout\Controller\Context;
use MakinaCorpus\Ucms\Layout\ContextManager;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteAttachEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteCloneEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvents;
use MakinaCorpus\Ucms\Site\SiteManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles compositions auto-creation and node clone operations layout cloning.
 */
final class NodeEventSubscriber implements EventSubscriberInterface
{
    private $context;
    private $database;
    private $siteManager;

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            NodeEvent::EVENT_INSERT => [
                ['onInsert', -10]
            ],
            SiteEvents::EVENT_POST_INIT => [
                ['onSitePostInit', 0]
            ],
            SiteEvents::EVENT_CLONE => [
                ['onSiteClone', 0]
            ],
            SiteEvents::EVENT_ATTACH => [
                ['onAttach', -64] // Must happen after the reference has been done
            ],
        ];
    }

    /**
     * Default constructor
     *
     * @param Context $context
     * @param \DatabaseConnection $databaase
     * @param SiteManager $siteManager
     */
    public function __construct(Context $context, \DatabaseConnection $databaase, SiteManager $siteManager)
    {
        $this->context = $context;
        $this->database = $databaase;
        $this->siteManager = $siteManager;
    }

    /**
     * When cloning a node within a site, we must replace all its parent
     * references using the new new node identifier instead, in order to make
     * it gracefully inherit from the right layouts.
     */
    public function onInsert(NodeEvent $event)
    {
        $node = $event->getNode();

        // When inserting a node, site_id is always the current site context.
        if ($event->isClone() && $node->site_id) {

            $exists = (bool)$this
                ->database
                ->query(
                    "SELECT 1 FROM {ucms_site_node} WHERE nid = :nid AND site_id = :sid",
                    [':nid' => $node->parent_nid, ':sid' => $node->site_id]
                )
            ;

            if ($exists) {

                // On clone, the original node layout should be kept but owned
                // by the clone instead of the parent, IF AND ONLY IF the site
                // is the same.
                $this
                    ->database
                    ->query(
                        "UPDATE {layout} SET nid = :clone WHERE nid = :parent AND site_id = :site",
                        [
                            ':clone'  => $node->id(),
                            ':parent' => $node->parent_nid,
                            ':site'   => $node->site_id,
                        ]
                    )
                ;

                // The same way, if the original node was present in some site
                // layout, it must be replaced by the new one, IF AND ONLY IF
                // the site is the same
                switch ($this->database->driver()) {

                    case 'mysql':
                        $sql = "
                            UPDATE {layout_data} d
                            JOIN {layout} l ON l.id = d.layout_id
                            SET
                                d.item_id = :clone
                            WHERE
                                d.item_id = :parent
                                AND d.item_type = 'node'
                                AND l.site_id = :site
                        ";
                        break;

                    default:
                        $sql = "
                            UPDATE {layout_data} AS d
                            SET
                                item_id = :clone
                            FROM {layout} l
                            WHERE
                                l.id = d.layout_id
                                AND d.item_type = 'node'
                                AND d.item_id = :parent
                                AND l.site_id = :site
                        ";
                        break;
                }

                $this->database->query($sql, [
                    ':clone'  => $node->id(),
                    ':parent' => $node->parent_nid,
                    ':site'   => $node->site_id,
                ]);
            }
        }
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
            ->database
            ->query(
                "
                INSERT INTO {layout} (site_id, nid, region)
                SELECT
                    :target, usn.nid, ul.region
                FROM {layout} ul
                JOIN {ucms_site_node} usn ON
                    ul.nid = usn.nid
                    AND usn.site_id = :target
                WHERE
                    ul.site_id = :source
                    AND NOT EXISTS (
                        SELECT 1
                        FROM {layout} s_ul
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
            ->database
            ->query(
                "
                INSERT INTO {layout_data}
                    (layout_id, item_type, item_id, style, position, options)
                SELECT
                    target_ul.id,
                    uld.item_type,
                    uld.item_id,
                    uld.style,
                    uld.position,
                    uld.options
                FROM {layout} source_ul
                JOIN {layout_data} uld ON
                    source_ul.id = uld.layout_id
                    AND source_ul.site_id = :source
                JOIN {node} n ON n.nid = uld.nid
                JOIN {layout} target_ul ON
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

    /**
     * When referencing a node on site, we clone its original layout as well
     * so the user gets an exact copy of the page.
     */
    public function onAttach(SiteAttachEvent $event)
    {
        // On 08/09/2016, I stumbled upon this piece of code. Long story short:
        // when you reference a node in a site, it duplicates the original site
        // layout in the new site.
        //
        // While I could see some kind of use for this, I am not sure this is
        // really necessary.
        //
        // I am quite sure that the original wanted behavior was on node clone
        // and not on node reference: when you want to edit a node that's not
        // yours, on your site, the application propose that you may clone it on
        // the site instead of editing the original node, at this exact point in
        // time, you do need to duplicate layouts.

        // Do no run when in edit mode
        if ($this->context->hasToken()) {
            return;
        }

        $siteIdList = $event->getSiteIdList();
        /* @var \Drupal\node\NodeInterface[] $nodeList */
        $nodeList = $this->entityManager->getStorage('node')->loadMultiple($event->getNodeIdList());

        $pageContext = $this->contextManager->getPageContext();
        $storage = $pageContext->getStorage();

        // @todo Find a better way
        foreach ($siteIdList as $siteId) {
            foreach ($nodeList as $node) {

                if (!$node->site_id) {
                    continue;
                }

                // Ensure a layout does not already exists (for example when
                // cloning a node, the layout data already has been inserted
                // if the original was existing).
                $exists = (bool)$this
                    ->db
                    ->query(
                        "SELECT 1 FROM {ucms_layout} WHERE nid = :nid AND site_id = :sid",
                        [':nid' => $node->id(), ':sid' => $siteId]
                    )
                    ->fetchField()
                ;

                if ($exists) {
                    return;
                }

                $layout = $storage->findForNodeOnSite($node->id(), $node->site_id);

                if ($layout) {
                    $clone = clone $layout;
                    $clone->setId(null);
                    $clone->setSiteId($siteId);

                    foreach ($clone->getAllRegions() as $region) {
                        $region->toggleUpdateStatus(true);
                    }

                    $storage->save($clone);
                }
            }
        }
    }
}
