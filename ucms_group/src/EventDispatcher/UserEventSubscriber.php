<?php

namespace MakinaCorpus\Ucms\Group\EventDispatcher;

use Drupal\Core\Entity\EntityManager;
use MakinaCorpus\Ucms\Site\GroupManager;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\User\EventDispatcher\UserEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 *
 * 1. If the user is created as being a webmaster of a site, attach it directly
 *    to the site's group if any: this is done in the onWebmasterAttach() event
 *    listener callback.
 *
 * 2. If the user creating the user is admin of one or more groups arbitrarily
 *    take the first group to attach the user, which is done in the onUserAdd()
 *    event listener callback.
 *
 * There is no option 3, as of today this should be enough.
 *
 * Do not attach users to group if they are not dashboard users
 * (intranet users have no use in being attached to groups).
 */
class UserEventSubscriber implements EventSubscriberInterface
{
    private $entityManger;
    private $groupManager;

    /**
     * Default constructor
     */
    public function __construct(GroupManager $groupManager, EntityManager $entityManager)
    {
        $this->entityManger = $entityManager;
        $this->groupManager = $groupManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'site:webmaster_add_new' => [
                ['onWebmasterAttach', 0]
            ],
            'user:add' => [
                ['onUserAdd', 0]
            ],
        ];
    }

    /**
     * Attach user to group whenever it is set as webmaster in a site
     */
    public function onWebmasterAttach(SiteEvent $event)
    {
        if (!$webmasterUserId = $event->getArgument('webmaster_id')) {
            return;
        }

        if ($groupId = $event->getSite()->getGroupId()) {
            $found = false;
            $webmaster = $this->entityManger->getStorage('user')->load($webmasterUserId);

            foreach ($this->groupManager->getUserGroups($webmaster) as $groupAccess) {
                if ($groupId === $groupAccess->getGroupId()) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $this->groupManager->addMember($groupId, $webmasterUserId);
            }
        }
    }

    /**
     * Whenever a user gets created, attach it to the most relevant group.
     */
    public function onUserAdd(UserEvent $event)
    {
        $userId = $event->getCreatedUserId();
        $currentUserId = $event->getUserId();

        $webmaster = $this->entityManger->getStorage('user')->load($currentUserId);

        foreach ($this->groupManager->getUserGroups($webmaster) as $groupAccess) {
            $this->groupManager->addMember($groupAccess->getGroupId(), $userId);
            break;
        }
    }
}
