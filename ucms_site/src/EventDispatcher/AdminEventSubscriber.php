<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\EventDispatcher\AdminTableEvent;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Admin pages alteration
 */
class AdminEventSubscriber implements EventSubscriberInterface
{
    use StringTranslationTrait;

    private $siteManager;

    public function __construct(SiteManager $siteManager)
    {
        $this->siteManager = $siteManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            'admin:table:ucms_user_profile' => [
                ['onUserProfileDetails', 0]
            ],
        ];
    }

    public function onUserProfileDetails(AdminTableEvent $event)
    {
        $table = $event->getTable();

        /** @var \Drupal\Core\Session\AccountInterface $account */
        $account = $table->getAttribute('user');

        $sites = $this->siteManager->loadOwnSites($account);
        $access = $this->siteManager->getAccess();

        $requests = [];
        foreach ($sites as $id => $site) {
            $siteName = l($site->getAdminTitle(), 'admin/dashboard/site/' . $site->id); //@todo generate url

            if ($site->getState() == SiteState::REQUESTED) {
                $requests[] = $siteName;
                unset($sites[$id]);
            } else {
                $roleName = $access->getRelativeRoleName($access->getUserRole($account, $site)->getRole());
                $sites[$id] = $siteName . ' (' . check_plain($roleName) . ')';
            }
        }

        if ($sites) {
            $sites = implode('<br/>', $sites);
        } else {
            $sites = $this->t("No site");
        }

        $table->addHeader($this->t("Sites"));
        $table->addRow($this->t("Sites"), $sites);

        if ($account->hasPermission(Access::PERM_SITE_REQUEST)) {

            if ($requests) {
                $requests = implode('<br/>', $requests);
            } else {
                $requests = $this->t("No request");
            }

            $table->addRow(t("Pending requests"), $requests);
        }
    }
}
