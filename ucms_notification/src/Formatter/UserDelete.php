<?php


namespace MakinaCorpus\Ucms\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\NotificationInterface;
use MakinaCorpus\Drupal\APubSub\Notification\AbstractNotificationFormatter;


class UserDelete extends AbstractNotificationFormatter
{
    /**
     * {inheritdoc}
     */
    protected function getTypeLabelVariations($count)
    {
        return ["@count user", "@count users"];
    }


    /**
     * {inheritdoc}
     */
    protected function getVariations(NotificationInterface $notification, array &$args = [])
    {
        $data = $notification->getData();
        $args['@title'] = $data['name'];

        if ($name = $this->getUserAccountName($notification)) {
            $args['@name'] = $name;
            return [
                "@title has been deleted by @name",
                "@title have been deleted by @name",
            ];
        } else {
            return [
                "@title has been deleted",
                "@title have been deleted",
            ];
        }
    }
}



