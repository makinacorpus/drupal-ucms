<?php

namespace MakinaCorpus\Ucms\Contrib\EventDispatcher;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This subscriber will collect media references within text fields.
 */
class MediaReferenceEventListener implements EventSubscriberInterface
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

    public function onCollect(NodeReferenceCollectEvent $event)
    {
        $node = $event->getNode();

        foreach ($this->getSearchableFields($node->bundle()) as $field) {

            if (!$items = field_get_items('node', $node, $field)) {
                continue;
            }

            foreach ($items as $item) {
                $matches = [];
                if (preg_match_all('@data-media-nid=([^\s]+)@ims', $item['value'], $matches)) {
                    $idList = array_map(function ($id) { return trim($id, '\'"'); }, $matches[1]);
                    $event->addReferences('media', $idList, $field);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            NodeReferenceCollectEvent::EVENT_NAME => [
                ['onCollect', 0]
            ],
        ];
    }
}