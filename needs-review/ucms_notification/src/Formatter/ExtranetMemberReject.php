<?php


namespace MakinaCorpus\Ucms\Notification\Formatter;

use Drupal\Core\Session\AccountInterface;

use MakinaCorpus\APubSub\Notification\NotificationInterface;
use MakinaCorpus\Ucms\Notification\Formatter\AbstractUserNotificationFormatter;
use MakinaCorpus\Ucms\Site\SiteManager;


class ExtranetMemberReject extends AbstractUserNotificationFormatter
{
    /**
     * @var SiteManager
     */
    protected $siteManager;

    /**
     * Constructor.
     *
     * @param AccountInterface $account
     * @param SiteManager $siteManager
     */
    public function __construct(AccountInterface $account, SiteManager $siteManager)
    {
        parent::__construct($account);
        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function getVariations(NotificationInterface $notification, array &$args = [])
    {
        $site = $this->siteManager->getStorage()->findOne($notification['site_id']);

        $args['@title'] = $notification['name'];
        $args['@site']  = $site->getTitle();
        $args['!name']  = $this->getUserAccountName($notification);

        return [
            "Registration of @title on @site has been rejected by !name",
            "Registrations of @title on @site have been rejected by !name",
        ];
    }

    function getTranslations()
    {
        $this->t("Registration of @title on @site has been rejected by !name");
        $this->t("Registrations of @title on @site have been rejected by !name");
    }
}


