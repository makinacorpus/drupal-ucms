<?php


namespace MakinaCorpus\Ucms\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\NotificationInterface;
use MakinaCorpus\Ucms\Notification\Formatter\AbstractUserNotificationFormatter;


class UserEnable extends AbstractUserNotificationFormatter
{
    /**
     * {inheritdoc}
     */
    protected function getVariations(NotificationInterface $notification, array &$args = [])
    {
        if ($name = $this->getUserAccountName($notification)) {
            $args['@name'] = $name;
            return [
                "@title has been enabled by @name",
                "@title have been enabled by @name",
            ];
        } else {
            return [
                "@title has been enabled",
                "@title have been enabled",
            ];
        }
    }

    function getTranslations()
    {
        $this->t("@title has been enabled by @name");
        $this->t("@title have been enabled by @name");
        $this->t("@title has been enabled");
        $this->t("@title have been enabled");
    }
}



