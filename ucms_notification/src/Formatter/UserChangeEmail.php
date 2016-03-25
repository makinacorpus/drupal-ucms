<?php


namespace MakinaCorpus\Ucms\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\NotificationInterface;
use MakinaCorpus\Ucms\Notification\Formatter\AbstractUserNotificationFormatter;


class UserChangeEmail extends AbstractUserNotificationFormatter
{
    /**
     * {inheritdoc}
     */
    protected function getVariations(NotificationInterface $notification, array &$args = [])
    {
        if ($name = $this->getUserAccountName($notification)) {
            $args['@name'] = $name;
            return [
                "@title's email has been changed by @name",
                "Emails of @title have been changed by @name",
            ];
        } else {
            return [
                "@title's email has been changed",
                "Emails of @title have been changed",
            ];
        }
    }

    function getTranslations()
    {
        $this->t("@title's email has been changed by @name");
        $this->t("Emails of @title have been changed by @name");
        $this->t("@title's email has been changed by");
        $this->t("Emails of @title have been changed");
    }
}

