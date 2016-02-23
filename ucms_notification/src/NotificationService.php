<?php

namespace MakinaCorpus\Ucms\Notification;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityStorageInterface;

use MakinaCorpus\APubSub\Error\ChannelDoesNotExistException;
use MakinaCorpus\APubSub\Field;
use MakinaCorpus\APubSub\Notification\NotificationService as BaseNotificationService;

/**
 * Handles site access
 */
class NotificationService
{
    /**
     * @var BaseNotificationService
     */
    private $notificationService;

    /**
     * @var EntityStorageInterface
     */
    private $userStorage;

    /**
     * Default constructor
     *
     * @param BaseNotificationService $notificationService
     * @param EntityManager $entityManager
     */
    public function __construct(BaseNotificationService $notificationService, EntityManager $entityManager)
    {
        $this->notificationService = $notificationService;
        $this->userStorage = $entityManager->getStorage('user');
    }

    /**
     * Get user account
     *
     * @param int $userId
     *
     * @return stdClass
     */
    private function getUserAccount($userId)
    {
        $user = $this->userStorage->load($userId);

        if (!$user) {
            throw new \InvalidArgumentException(sprintf("User %d does not exist", $userId));
        }

        return $user;
    }

    /**
     * Get default chan list
     *
     * @return string[]
     *   Keys are channel identifiers, values are english human names
     */
    public function getDefaultChanList()
    {
        return [
            'admin:client'  => "User account management",
            'admin:content' => "Content management",
            'admin:label'   => "Label management",
            'admin:seo'     => "SEO management",
            'admin:site'    => "Site management",
        ];
    }

    /**
     * Ensure user mandatory subscriptions
     *
     * @param int $userId
     */
    public function refreshSubscriptionsFor($userId)
    {
        $default    = array_keys($this->getDefaultChanList());
        $valid      = ['client:' . $userId];
        $account    = $this->getUserAccount($userId);

        if (user_access(Access::PERM_NOTIF_CONTENT, $account)) {
            $valid[] = 'admin:content';
        }
        if (user_access(Access::PERM_NOTIF_LABEL, $account)) {
            $valid[] = 'admin:label';
        }
        if (user_access(Access::PERM_NOTIF_SEO, $account)) {
            $valid[] = 'admin:seo';
        }
        if (user_access(Access::PERM_NOTIF_SITE, $account)) {
            $valid[] = 'admin:site';
        }
        if (user_access(Access::PERM_NOTIF_USER, $account)) {
            $valid[] = 'admin:client';
        }

        $remove = array_diff($default, $valid);

        if ($valid) {
            $suber = $this->notificationService->getSubscriber($userId);

            foreach ($valid as $chanId) {

                if ($suber->hasSubscriptionFor($chanId)) {
                    continue;
                }

                try {
                    $suber->subscribe($chanId);
                } catch (ChannelDoesNotExistException $e) {
                    $this->notificationService->getBackend()->createChannel($chanId);
                    $suber->subscribe($chanId);
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
}
