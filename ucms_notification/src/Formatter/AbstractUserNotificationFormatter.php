<?php


namespace MakinaCorpus\Ucms\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\NotificationInterface;
use MakinaCorpus\Drupal\APubSub\Notification\AbstractNotificationFormatter;

abstract class AbstractUserNotificationFormatter extends AbstractNotificationFormatter
{
    /**
     * {@inheritdoc}
     */
    public function getURI(NotificationInterface $interface)
    {
        $userIdList = $interface->getResourceIdList();
        if (count($userIdList) === 1) {
            return 'admin/dashboard/user/' . reset($userIdList);
        }
    }

    /**
     * {inheritdoc}
     */
    protected function getTitles($idList)
    {
        $titles = [];
        foreach (user_load_multiple($idList) as $user) {
            $titles[$user->uid] = format_username($user);
        }
        return $titles;
    }

    /**
     * {inheritdoc}
     */
    protected function getTypeLabelVariations($count)
    {
        return ["@count user", "@count users"];
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareImageURI(NotificationInterface $notification)
    {
        return "user";
    }
}
