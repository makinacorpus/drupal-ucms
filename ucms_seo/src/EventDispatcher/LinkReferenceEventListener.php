<?php

namespace MakinaCorpus\Ucms\Seo\EventDispatcher;

use MakinaCorpus\Ucms\Contrib\EventDispatcher\NodeReferenceCollectEvent;
use MakinaCorpus\ULink\EntityLinkGenerator;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This subscriber will collect linked content within text fields.
 */
class LinkReferenceEventListener implements EventSubscriberInterface
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

    private function extractData(array $items)
    {
        $ret = [];

        foreach ($items as $values) {

            $matches = [];
            if (preg_match_all(EntityLinkGenerator::SCHEME_REGEX, $values['value'], $matches)) {
                $ret = array_merge($ret, $matches[3]);
            }

            $matches = [];
            if (preg_match_all(EntityLinkGenerator::STACHE_REGEX, $values['value'], $matches)) {
                $ret = array_merge($ret, $matches[2]);
            }
        }

        return array_unique($ret);
    }

    public function onCollect(NodeReferenceCollectEvent $event)
    {
        $node = $event->getNode();

        foreach ($this->getSearchableFields($node->bundle()) as $field) {

            if (!$items = field_get_items('node', $node, $field)) {
                continue;
            }

            $idList = $this->extractData($items);
            if ($idList) {
                $event->addReferences('link', $idList, $field);
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
