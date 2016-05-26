<?php

namespace MakinaCorpus\Ucms\Contrib\EventDispatcher;

use MakinaCorpus\Ucms\Search\EventDispatcher\NodeIndexEvent;
use MakinaCorpus\Ucms\Search\NodeIndexer;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Index node fields.
 */
class NodeIndexEventSubscriber implements EventSubscriberInterface
{
    private function getSearchableFields($bundle)
    {
        $ret = [];

        foreach (field_info_instances('node', $bundle) as $name => $info) {
            $field = field_info_field($info['field_name']);
            if (in_array($field['type'], ['text', 'text_long', 'text_with_summary'], true)) {
                $ret[] = $name;
            }
        }

        return $ret;
    }

    public function onIndex(NodeIndexEvent $event)
    {
        $node = $event->getNode();

        foreach ($this->getSearchableFields($node->bundle()) as $field) {
            $event->fieldToFulltext($field);
        }

        $event->fieldToTagIdList('tags');
    }

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            NodeIndexer::EVENT_INDEX => [
                ['onIndex', 0]
            ],
        ];
    }
}