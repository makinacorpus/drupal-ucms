<?php

namespace MakinaCorpus\Ucms\Group\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Calista\Event\AdminTableEvent;
use MakinaCorpus\Ucms\Site\GroupManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Admin pages alteration
 */
class AdminEventSubscriber implements EventSubscriberInterface
{
    use StringTranslationTrait;

    private $groupManager;

    public function __construct(GroupManager $groupManager)
    {
        $this->groupManager = $groupManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            AdminTableEvent::getEventName('ucms_site_details') => [
                ['onSiteAdminDetails', 0]
            ],
            AdminTableEvent::getEventName('ucms_user_profile') => [
                ['onUserProfileDetails', 0]
            ],
        ];
    }

    public function onSiteAdminDetails(AdminTableEvent $event)
    {
        $table  = $event->getTable();
        $site   = $table->getAttribute('site');

        if (!$site) {
            return;
        }

        $group = $this->groupManager->getSiteGroup($site);
        if (!$group) {
            return;
        }

        $table->addHeader($this->t("Group"));
        $table->addRow($this->t("Groups"), l($group->getTitle(), 'admin/dashboard/group/' . $group->getId()));
    }

    public function onUserProfileDetails(AdminTableEvent $event)
    {
        $table = $event->getTable();

        /** @var \Drupal\Core\Session\AccountInterface $account */
        $account = $table->getAttribute('user');


        $list = [];
        $accessList = $this->groupManager->getUserGroups($account);
        foreach ($this->groupManager->loadGroupsFrom($accessList) as $group) {
            $list[] = l($group->getTitle(), 'admin/dashboard/group/' . $group->getId());
        }

        $table->addHeader($this->t("Group"));
        $table->addRow($this->t("Groups"), implode('<br/>', $list));
    }
}
