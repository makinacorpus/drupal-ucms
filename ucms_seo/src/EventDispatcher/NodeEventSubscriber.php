<?php

namespace MakinaCorpus\Ucms\Seo\EventDispatcher;

use MakinaCorpus\Ucms\Contrib\EventDispatcher\NodeEvent;
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

    public function __construct(
        \DatabaseConnection $db,
        SeoService $service,
        SiteManager $manager,
        StoreLocatorFactory $locatorFactory
    ) {
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

    protected function onSaveRebuildLocatorAliases(NodeEvent $event)
    {
        $node = $event->getNode();

        // @todo inject the variable instead of using variable_get()
        if (in_array($node->type, variable_get('ucms_seo_store_locator_content_types', []), true)) {
            $storeLocator = $this->locatorFactory->create($node);
            $storeLocator->rebuildAliases();
        } else {
            $storeLocator = $this->locatorFactory->create();
            // © Singing don't worry about a thing,
            // © Cause every little thing gonna be alright.
            // store locator won't rebuild ALL aliases but only ones which are
            // interesting for it ;-)
            $storeLocator->rebuildAliases($node);
        }
    }

    public function onSave(NodeEvent $event)
    {
        $this->onSaveEnsureSegment($event);
        $this->onSaveRebuildLocatorAliases($event);
        $this->onSaveStoreMeta($event);
    }

    public function onInsert(NodeEvent $event)
    {
        $node = $event->getNode();

        // When inserting a node, site_id is always the current site context.
        // Aliases should be merged on with the parent ones, since the parent
        // is going to be dereferenced from the site.
        if ($event->isClone() && $node->site_id) {
            $this->service->replaceNodeAliases($node->site_id, $node->parent_nid, $node->id());
        }
    }

    public function onDelete(NodeEvent $event)
    {
        $this->service->onAliasRemove($event->getNode());
    }

    private function onSaveStoreMeta(NodeEvent $event) {
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
