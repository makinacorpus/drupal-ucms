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
        parent::getVariations($notification, $args);
        if ($name = $this->getUserAccountName($notification)) {
            $args['@name'] = $name;
            return [
                "@title of type @type has been unpublished by @name",
                "@title of type @type have been unpublished by @name",
            ];
        } else {
            return [
                "@title of type @type has been unpublished",
                "@title of type @type have been unpublished",
            ];
        }
    }

    public function getTranslations()
    {
        $this->t("@title of type @type has been unpublished by @name");
        $this->t("@title of type @type have been unpublished by @name");
        $this->t("@title of type @type has been unpublished");
        $this->t("@title of type @type have been unpublished");
    }
}
