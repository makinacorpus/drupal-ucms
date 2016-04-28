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
        parent::getVariations($notification, $args);
        if ($name = $this->getUserAccountName($notification)) {
            $args['@name'] = $name;
            return [
                "@title of type @type has been updated by @name",
                "@title of type @type have been updated by @name",
            ];
        } else {
            return [
                "@title of type @type has been updated",
                "@title of type @type have been updated",
            ];
        }
    }

    public function getTranslations()
    {
        $this->t("@title of type @type has been updated by @name");
        $this->t("@title of type @type have been updated by @name");
        $this->t("@title of type @type has been updated");
        $this->t("@title of type @type have been updated");
    }
}
