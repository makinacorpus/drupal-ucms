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
        parent::getVariations($notification, $args);
        if ($name = $this->getUserAccountName($notification)) {
            $args['@name'] = $name;
            return [
                "@title of type @type has been published by @name",
                "@title of type @type have been published by @name",
            ];
        } else {
            return [
                "@title of type @type has been published",
                "@title of type @type have been published",
            ];
        }
    }

    public function getTranslations()
    {
        $this->t("@title of type @type has been published by @name");
        $this->t("@title of type @type have been published by @name");
        $this->t("@title of type @type has been published");
        $this->t("@title of type @type have been published");
    }
}
