<?php

namespace MakinaCorpus\Ucms\Seo\EventDispatcher;

use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeEvent;
use MakinaCorpus\Ucms\Seo\SeoService;
use MakinaCorpus\Ucms\Seo\StoreLocator\StoreLocatorFactory;
use MakinaCorpus\Ucms\Site\SiteManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NodeEventSubscriber implements EventSubscriberInterface
{
    private $db;
    private $service;
    private $manager;
    private $locatorFactory;

    static public function getSubscribedEvents()
    {
        return [
            NodeEvent::EVENT_DELETE => [
                ['onDelete', 0],
            ],
            NodeEvent::EVENT_INSERT => [
                // We need to pass before the ucms_site module for handling
                // aliases in case they get deleted once the parent node is
                // being removed from the site
                ['onInsert', -10],
            ],
            NodeEvent::EVENT_SAVE => [
                ['onSave', 0],
            ],
        ];
    }

    /**
     * Default constructor
     */
    public function __construct(\DatabaseConnection $db, SeoService $service, SiteManager $manager, StoreLocatorFactory $locatorFactory)
    {
        $this->db = $db;
        $this->service = $service;
        $this->manager = $manager;
        $this->locatorFactory = $locatorFactory;
    }

    protected function onSaveEnsureSegment(NodeEvent $event)
    {
        $node = $event->getNode();

        $segment = null;

        // This comes from the node form, and is the only case it'd happen
        if (property_exists($node, 'ucms_seo_segment') && !empty($node->ucms_seo_segment)) {
            $segment = $node->ucms_seo_segment;
        } else {
            // Automatically generate the first segment version from the node
            // title, force small length when not driven by user input
            $title = $node->getTitle();
            if ($title) {
                $segment = $this->service->normalizeSegment($title, 60);
            }
        }

        $this->service->setNodeSegment($node, $segment);
    }

    public function onSave(NodeEvent $event)
    {
        $this->onSaveEnsureSegment($event);
        $this->onSaveStoreMeta($event);
    }

    public function onInsert(NodeEvent $event)
    {
        $node = $event->getNode();

        // When inserting a node, site_id is always the current site context.
        // Aliases should be merged on with the parent ones, since the parent
        // is going to be dereferenced from the site.
        if ($event->isClone() && $node->site_id) {
            $this->service->onAliasChange([$node->id(), $node->parent_nid]);
        }
    }

    public function onDelete(NodeEvent $event)
    {
        $this->service->onAliasChange([$event->getNode()->id()]);
    }

    private function onSaveStoreMeta(NodeEvent $event)
    {
        $node = $event->getNode();

        $values = [];

        // This comes from the node form, and is the only case it'd happen
        if (property_exists($node, 'ucms_seo_title') && !empty($node->ucms_seo_title)) {
            $values['title'] = $node->ucms_seo_title;
        }
        if (property_exists($node, 'ucms_seo_description') && !empty($node->ucms_seo_description)) {
            $values['description'] = $node->ucms_seo_description;
        }

        $this->service->setNodeMeta($node, $values);
    }
}
