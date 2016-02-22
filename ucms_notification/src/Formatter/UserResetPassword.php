<?php


namespace MakinaCorpus\Ucms\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\NotificationInterface;
use MakinaCorpus\Ucms\Notification\Formatter\AbstractUserNotificationFormatter;


class UserResetPassword extends AbstractUserNotificationFormatter
{
    /**
     * {inheritdoc}
     */
    protected function getVariations(NotificationInterface $notification, array &$args = [])
    {
        if ($name = $this->getUserAccountName($notification)) {
            $args['@name'] = $name;
            return [
                "@title's password has been resetted by @name",
                "Passwords of @title have been resetted by @name",
            ];
        } else {
            return [
                "@title's password has been resetted",
                "Passwords of @title have been resetted",
            ];
        }
    }
}



