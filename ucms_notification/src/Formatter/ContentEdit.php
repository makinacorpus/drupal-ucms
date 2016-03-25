<?php


namespace MakinaCorpus\Ucms\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\NotificationInterface;
use MakinaCorpus\Ucms\Notification\Formatter\AbstractContentNotificationFormatter;


class ContentEdit extends AbstractContentNotificationFormatter
{
    /**
     * {@inheritdoc}
     */
    protected function getVariations(NotificationInterface $notification, array &$args = [])
    {
        if ($name = $this->getUserAccountName($notification)) {
            $args['@name'] = $name;
            return [
                "@title has been updated by @name",
                "@title have been updated by @name",
            ];
        } else {
            return [
                "@title has been updated",
                "@title have been updated",
            ];
        }
    }

    public function getTranslations()
    {
        $this->t("@title has been updated by @name");
        $this->t("@title have been updated by @name");
        $this->t("@title has been updated");
        $this->t("@title have been updated");
    }
}
