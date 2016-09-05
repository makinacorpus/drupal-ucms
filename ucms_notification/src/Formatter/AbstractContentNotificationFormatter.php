<?php

namespace MakinaCorpus\Ucms\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\NotificationInterface;
use MakinaCorpus\Drupal\APubSub\Notification\AbstractNotificationFormatter;
use MakinaCorpus\Ucms\Contrib\TypeHandler;

abstract class AbstractContentNotificationFormatter extends AbstractNotificationFormatter
{
    /**
     * @var TypeHandler
     */
    private $typeHandler;

    /**
     * {@inheritdoc}
     */
    public function getURI(NotificationInterface $interface)
    {
        $contentIdList = $interface->getResourceIdList();
        if (count($contentIdList) === 1) {
            return 'node/'.reset($contentIdList);
        }
    }

    /**
     * @param TypeHandler $typeHandler
     */
    public function setTypeHandler($typeHandler)
    {
        $this->typeHandler = $typeHandler;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTitles($idList)
    {
        $titles = [];
        
        foreach (node_load_multiple($idList) as $node) {
            if (!empty($node->title)) {
                $titles[$node->nid] = $node->title;
            }
        }

        return $titles;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTypeLabelVariations($count)
    {
        return ["@count content", "@count contents"];
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareImageURI(NotificationInterface $notification)
    {
        $contentIdList = $notification->getResourceIdList();
        if (count($contentIdList) === 1) {
            if ($node = node_load(reset($contentIdList))) {
                return in_array($node->type, $this->typeHandler->getContentTypes()) ? "file" : "picture";
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getVariations(NotificationInterface $notification,array &$args = [])
    {
        $idList = $notification->getResourceIdList();
        // This is already cached by $this->getTitles()
        foreach (node_load_multiple($idList) as $node) {
            $args['@type'] = t(node_type_get_name($node));
        }
    }
}
