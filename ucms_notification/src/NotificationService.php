<?php

namespace MakinaCorpus\Ucms\Notification;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Session\AccountInterface;
use MakinaCorpus\APubSub\Error\ChannelDoesNotExistException;
use MakinaCorpus\APubSub\Field;
use MakinaCorpus\APubSub\Notification\NotificationService as BaseNotificationService;
use MakinaCorpus\Ucms\Site\GroupManager;

/**
 * Base helper methods for handling notifications
 */
class NotificationService
{
    private $notificationService;
    private $entityManager;
    private $groupManager;

    /**
     * Default constructor
     *
     * @param BaseNotificationService $notificationService
     * @param EntityManager $entityManager
     */
    public function __construct(BaseNotificationService $notificationService, EntityManager $entityManager, GroupManager $groupManager = null)
    {
        $this->notificationService = $notificationService;
        $this->entityManager = $entityManager;
        $this->groupManager = $groupManager;
    }

    /**
     * Get notification service
     *
     * @return BaseNotificationService
     */
    public function getNotificationService()
    {
        return $this->notificationService;
    }

    /**
     * Get user account
     *
     * @param int $userId
     *
     * @return AccountInterface
     */
    private function getUserAccount($userId)
    {
        $user = $this->entityManager->getStorage('user')->load($userId);

        if (!$user) {
            throw new \InvalidArgumentException(sprintf("User %d does not exist", $userId));
        }

        return $user;
    }

    /**
     * Ensure user mandatory subscriptions
     *
     * Always remember that having the view notifications permissions does not
     * imply that you can see the content, so users might receive notifications
     * about content they cannot see; doing otherwise would mean to recheck
     * all permissions/access rights for all users on every content update which
     * is not something doable at all.
     *
     * @param int $userId
     */
    public function refreshSubscriptionsFor($userId)
    {
        $valid      = ['client:' . $userId];
        $account    = $this->getUserAccount($userId);
        $default    = [
            'admin:client',
            'admin:content',
            'admin:label',
            'admin:seo',
            'admin:site',
        ];

        if (!$account->isAuthenticated() || !$account->status) {
            // Do not allow deactivated or anymous user to incidently have
            // subscribed channels, it would be terrible performance loss
            // for pretty much no reason.
            $valid = [];

        } else {
            // Must handle user groupes instead
            if ($this->groupManager) {
                // @todo We MUST handle a role within the group instead of using
                //   global permissions, this must be fixed in a near future.
                $accessList = $this->groupManager->getUserGroups($account);
                if ($accessList) {
                    // Add group sites to user, but do not add anything else, when
                    // using the platform with groups, user with no groups are not
                    // considered and must not receive anything.
                    if ($account->hasPermission(Access::PERM_NOTIF_CONTENT)) {
                        foreach ($accessList as $access) {
                            $valid[] = 'admin:content:' . $access->getGroupId();
                        }
                    }
                    if ($account->hasPermission(Access::PERM_NOTIF_SITE)) {
                        foreach ($accessList as $access) {
                            $valid[] = 'admin:site:' . $access->getGroupId();
                        }
                    }
                }

            } else {
                // Normal behaviors, when the group module is not enabled.
                if ($account->hasPermission(Access::PERM_NOTIF_CONTENT)) {
                    $valid[] = 'admin:content';
                }
                if ($account->hasPermission(Access::PERM_NOTIF_SITE)) {
                    $valid[] = 'admin:site';
                }
            }

            // Those three, as of today, are not group-dependent.
            if ($account->hasPermission(Access::PERM_NOTIF_LABEL)) {
                $valid[] = 'admin:label';
            }
            if ($account->hasPermission(Access::PERM_NOTIF_SEO)) {
                $valid[] = 'admin:seo';
            }
            if ($account->hasPermission(Access::PERM_NOTIF_USER)) {
                $valid[] = 'admin:client';
            }
        }

        $remove = array_diff($default, $valid);

        if ($valid) {
            $subscriber = $this->notificationService->getSubscriber($userId);

            foreach ($valid as $chanId) {

                if ($subscriber->hasSubscriptionFor($chanId)) {
                    continue;
                }

                try {
                    $subscriber->subscribe($chanId);
                } catch (ChannelDoesNotExistException $e) {
                    $this->notificationService->getBackend()->createChannel($chanId);
                    $subscriber->subscribe($chanId);
                }
            }
        }

        if ($remove) {
            $this->deleteSubscriptionsFor($userId, $remove);
        }
    }

    /**
     * Delete user subscriptions
     *
     * @param int $userId
     * @param string[] $chanIdList
     */
    public function deleteSubscriptionsFor($userId, $chanIdList = null)
    {
        $conditions = [
            Field::SUBER_NAME => $this
                ->notificationService
                ->getSubscriberName($userId),
        ];

        if ($chanIdList) {
            $conditions[Field::CHAN_ID] = $chanIdList;
        }

        $this
            ->notificationService
            ->getBackend()
            ->fetchSubscriptions($conditions)
            ->delete()
        ;
    }

    /**
     * Is the user subscribed to this channel?
     *
     * @param int $userId
     * @param string $chanId
     */
    public function isSubscribedTo($userId, $chanId)
    {
        return $this->notificationService->getSubscriber($userId)->hasSubscriptionFor($chanId);
    }
}
