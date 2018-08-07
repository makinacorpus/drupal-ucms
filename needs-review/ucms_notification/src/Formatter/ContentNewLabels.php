<?php


namespace MakinaCorpus\Ucms\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\NotificationInterface;
use MakinaCorpus\Ucms\Label\LabelManager;
use MakinaCorpus\Ucms\Notification\Formatter\AbstractContentNotificationFormatter;


class ContentNewLabels extends AbstractContentNotificationFormatter
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
                "@title of type @type has been associated with some of the labels you subscribed to by @name",
                "@title of type @type have been associated with some of the labels you subscribed to by @name",
            ];
        } else {
            return [
                "@title of type @type has been associated with some of the labels you subscribed to",
                "@title of type @type have been associated with some of the labels you subscribed to",
            ];
        }
    }

    public function getTranslations()
    {
        $this->t("@title of type @type has been associated with some of the labels you subscribed to by @name");
        $this->t("@title of type @type have been associated with some of the labels you subscribed to by @name");
        $this->t("@title of type @type has been associated with some of the labels you subscribed to");
        $this->t("@title of type @type have been associated with some of the labels you subscribed to");
    }
}
