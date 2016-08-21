<?php


namespace MakinaCorpus\Ucms\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\NotificationInterface;
use MakinaCorpus\Ucms\Notification\Formatter\AbstractUserNotificationFormatter;
use MakinaCorpus\Ucms\Site\SiteManager;


class ExtranetMemberAccept extends AbstractUserNotificationFormatter
{
    /**
     * @var SiteManager
     */
    protected $siteManager;

    /**
     * Constructor.
     *
     * @param SiteManager $siteManager
     */
    public function __construct(SiteManager $siteManager)
    {
        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function getVariations(NotificationInterface $notification, array &$args = [])
    {
        $site = $this->siteManager->getStorage()->findOne($notification['site_id']);
        $args['@site'] = $site->getTitle();
        $args['!name'] = $this->getUserAccountName($notification);

        return [
            "Registration of @title on @site has been accepted by !name",
            "Registrations of @title on @site have been accepted by !name",
        ];
    }

    function getTranslations()
    {
        $this->t("Registration of @title on @site has been accepted by !name");
        $this->t("Registrations of @title on @site have been accepted by !name");
    }
}
