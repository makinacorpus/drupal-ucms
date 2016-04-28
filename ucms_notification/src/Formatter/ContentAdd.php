<?php


namespace MakinaCorpus\Ucms\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\NotificationInterface;


class ContentAdd extends AbstractContentNotificationFormatter
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
                "@title of type @type has been created by @name",
                "@title of type @type have been created by @name",
            ];
        } else {
            return [
                "@title of type @type has been created",
                "@title of type @type have been created",
            ];
        }
    }

    function getTranslations()
    {
        $this->t("@title of type @type has been created by @name");
        $this->t("@title of type @type have been created by @name");
        $this->t("@title of type @type has been created");
        $this->t("@title of type @type have been created");
    }
}


