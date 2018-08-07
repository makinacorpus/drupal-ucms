<?php


namespace MakinaCorpus\Ucms\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\NotificationInterface;
use MakinaCorpus\Ucms\Notification\Formatter\AbstractUserNotificationFormatter;


class UserDisable extends AbstractUserNotificationFormatter
{
    /**
     * {inheritdoc}
     */
    protected function getVariations(NotificationInterface $notification, array &$args = [])
    {
        if ($name = $this->getUserAccountName($notification)) {
            $args['@name'] = $name;
            return [
                "@title has been disabled by @name",
                "@title have been disabled by @name",
            ];
        } else {
            return [
                "@title has been disabled",
                "@title have been disabled",
            ];
        }
    }

    function getTranslations()
    {
        $this->t("@title has been disabled by @name");
        $this->t("@title have been disabled by @name");
        $this->t("@title has been disabled");
        $this->t("@title have been disabled");
    }
}




