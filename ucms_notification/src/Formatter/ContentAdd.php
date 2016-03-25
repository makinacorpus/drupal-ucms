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
        if ($name = $this->getUserAccountName($notification)) {
            $args['@name'] = $name;

            return [
                "@title has been created by @name",
                "@title have been created by @name",
            ];
        } else {
            return [
                "@title has been created",
                "@title have been created",
            ];
        }
    }

    function getTranslations()
    {
        $this->t("@title has been created by @name");
        $this->t("@title have been created by @name");
        $this->t("@title has been created");
        $this->t("@title have been created");
    }
}


