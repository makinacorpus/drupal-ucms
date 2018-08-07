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
                "@title's password has been reset by @name",
                "Passwords of @title have been reset by @name",
            ];
        } else {
            return [
                "@title's password has been reset",
                "Passwords of @title have been reset",
            ];
        }
    }

    function getTranslations()
    {
        $this->t("@title's password has been reset by @name");
        $this->t("Passwords of @title have been reset by @name");
        $this->t("@title's password has been reset");
        $this->t("Passwords of @title have been reset");
    }
}



