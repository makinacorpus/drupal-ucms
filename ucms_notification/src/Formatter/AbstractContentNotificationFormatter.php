<?php


namespace MakinaCorpus\Ucms\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\NotificationInterface;
use MakinaCorpus\Drupal\APubSub\Notification\AbstractNotificationFormatter;


abstract class AbstractContentNotificationFormatter extends AbstractNotificationFormatter
{
    /**
     * {@inheritdoc}
     */
    public function getURI(NotificationInterface $interface)
    {
        $contentIdList = $interface->getResourceIdList();
        if (count($contentIdList) === 1) {
            return 'node/' . reset($contentIdList);
        }
    }

    /**
     * {inheritdoc}
     */
    protected function getTitles($idList)
    {
        $titles = [];
        foreach (node_load_multiple($idList) as $node) {
            $titles[$node->nid] = $node->title;
        }
        return $titles;
    }

    /**
     * {inheritdoc}
     */
    protected function getTypeLabelVariations($count)
    {
        return ["@count content", "@count content"];
    }
}