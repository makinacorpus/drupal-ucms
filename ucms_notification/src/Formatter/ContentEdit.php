<?php


namespace MakinaCorpus\Ucms\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\NotificationInterface;
use MakinaCorpus\Ucms\Notification\Formatter\AbstractContentNotificationFormatter;


class ContentEdit extends AbstractContentNotificationFormatter
{
    /**
     * {inheritdoc}
     */
    protected function getVariations(NotificationInterface $notification, array &$args = [])
    {
        if ($name = $this->getUserAccountName($notification)) {
            $args['@name'] = $name;
            return [
                "Content @title's has been updated by @name",
                "contents @title have been updated by @name",
            ];
        } else {
            return [
                "Content @title's has been updated",
                "Contents of @title have been updated",
            ];
        }
    }
}


