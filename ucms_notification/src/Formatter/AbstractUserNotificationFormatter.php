<?php

namespace MakinaCorpus\Ucms\Notification\Formatter;

use Drupal\Core\Session\AccountInterface;

use MakinaCorpus\APubSub\Notification\NotificationInterface;
use MakinaCorpus\Drupal\APubSub\Notification\AbstractNotificationFormatter;
use MakinaCorpus\Ucms\User\UserAccess;

abstract class AbstractUserNotificationFormatter extends AbstractNotificationFormatter
{
    /**
     * @var AccountInterface
     */
    protected $account;

    /**
     * Constructor.
     *
     * @param AccountInterface $account
     */
    public function __construct(AccountInterface $account)
    {
        $this->account = $account;
    }

    /**
     * {@inheritdoc}
     */
    public function getURI(NotificationInterface $interface)
    {
        $userIdList = $interface->getResourceIdList();
        if (count($userIdList) === 1 && $this->account->hasPermission(UserAccess::PERM_MANAGE_ALL)) {
            return 'admin/dashboard/user/' . reset($userIdList);
        }
    }

    /**
     * {inheritdoc}
     */
    protected function getTitles($idList)
    {
        $titles = [];
        foreach (user_load_multiple($idList) as $user) {
            $titles[$user->uid] = format_username($user);
        }
        return $titles;
    }

    /**
     * {inheritdoc}
     */
    protected function getTypeLabelVariations($count)
    {
        return ["@count user", "@count users"];
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareImageURI(NotificationInterface $notification)
    {
        return "user";
    }
}
