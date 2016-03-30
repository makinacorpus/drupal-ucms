<?php


namespace MakinaCorpus\Ucms\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\NotificationInterface;
use MakinaCorpus\Ucms\Notification\Formatter\AbstractContentNotificationFormatter;


class ContentUnpublish extends AbstractContentNotificationFormatter
{
    /**
     * {@inheritdoc}
     */
    protected function getVariations(NotificationInterface $notification, array &$args = [])
    {
        if ($name = $this->getUserAccountName($notification)) {
            $args['@name'] = $name;
            return [
                "@title has been unpublished by @name",
                "@title have been unpublished by @name",
            ];
        } else {
            return [
                "@title has been unpublished",
                "@title have been unpublished",
            ];
        }
    }

    public function getTranslations()
    {
        $this->t("@title has been unpublished by @name");
        $this->t("@title have been unpublished by @name");
        $this->t("@title has been unpublished");
        $this->t("@title have been unpublished");
    }
}
