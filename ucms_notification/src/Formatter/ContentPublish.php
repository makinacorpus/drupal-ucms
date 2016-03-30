<?php


namespace MakinaCorpus\Ucms\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\NotificationInterface;
use MakinaCorpus\Ucms\Notification\Formatter\AbstractContentNotificationFormatter;


class ContentPublish extends AbstractContentNotificationFormatter
{
    /**
     * {@inheritdoc}
     */
    protected function getVariations(NotificationInterface $notification, array &$args = [])
    {
        if ($name = $this->getUserAccountName($notification)) {
            $args['@name'] = $name;
            return [
                "@title has been published by @name",
                "@title have been published by @name",
            ];
        } else {
            return [
                "@title has been published",
                "@title have been published",
            ];
        }
    }

    public function getTranslations()
    {
        $this->t("@title has been published by @name");
        $this->t("@title have been published by @name");
        $this->t("@title has been published");
        $this->t("@title have been published");
    }
}
