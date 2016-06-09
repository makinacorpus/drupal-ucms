<?php

namespace MakinaCorpus\Ucms\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\NotificationInterface;
use MakinaCorpus\Ucms\Notification\Formatter\AbstractContentNotificationFormatter;

/**
 * A content has been flagged
 */
class ContentFlag extends AbstractContentNotificationFormatter
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
                "@title of type @type has been flagged by @name",
                "@title of type @type have been flagged by @name",
            ];
        } else {
            return [
                "@title of type @type has been flagged",
                "@title of type @type have been flagged",
            ];
        }
    }

    public function getTranslations()
    {
        $this->t("@title of type @type has been flagged by @name");
        $this->t("@title of type @type have been flagged by @name");
        $this->t("@title of type @type has been flagged");
        $this->t("@title of type @type have been flagged");
    }
}
