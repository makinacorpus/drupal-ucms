<?php


namespace MakinaCorpus\Ucms\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\NotificationInterface;
use MakinaCorpus\Drupal\APubSub\Notification\AbstractNotificationFormatter;


class ContentDelete extends AbstractNotificationFormatter
{
    /**
     * {@inheritdoc}
     */
    protected function getTypeLabelVariations($count)
    {
        return ["@count content", "@count contents"];
    }


    /**
     * {@inheritdoc}
     */
    protected function getVariations(NotificationInterface $notification, array &$args = [])
    {
        if ($name = $this->getUserAccountName($notification)) {
            $args['@name'] = $name;
            return [
                "@title has been deleted by @name",
                "@title have been deleted by @name",
            ];
        } else {
            return [
                "@title has been deleted",
                "@title have been deleted",
            ];
        }
    }

    public function getTranslations()
    {
        $this->t("@title has been deleted by @name");
        $this->t("@title have been deleted by @name");
        $this->t("@title has been deleted");
        $this->t("@title have been deleted");
    }
}
