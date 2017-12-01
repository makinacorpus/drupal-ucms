<?php

namespace MakinaCorpus\Ucms\Site\ACL;

use Drupal\Core\Session\AccountInterface;
use MakinaCorpus\ACL\Permission;
use MakinaCorpus\ACL\Resource;
use MakinaCorpus\ACL\Collector\EntryCollectorInterface;
use MakinaCorpus\ACL\Collector\EntryListBuilder;
use MakinaCorpus\ACL\Collector\ProfileCollectorInterface;
use MakinaCorpus\ACL\Collector\ProfileSetBuilder;
use MakinaCorpus\ACL\Converter\ResourceConverterInterface;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\GroupManager;
use MakinaCorpus\Ucms\Site\SiteManager;

final class UserEntryCollector implements EntryCollectorInterface, ProfileCollectorInterface, ResourceConverterInterface
{
    private $groupManager;
    private $siteManager;

    /**
     * Default constructor
     *
     * @param SiteManager $manager
     */
    public function __construct(SiteManager $siteManager, GroupManager $groupManager)
    {
        $this->groupManager = $groupManager;
        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $type, string $permission) : bool
    {
        return 'user' === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsType(string $type) : bool
    {
        return 'user' === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function convert($object)
    {
        if ($object instanceof AccountInterface) {
            return new Resource('user', $object->getId());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function collectEntryLists(EntryListBuilder $builder)
    {
        $account = $builder->getObject();

        if (!$account instanceof AccountInterface) {
            return; // can not happen, except if another resource coverter does
        }

        $builder->add(Access::PROFILE_USER_GOD, Access::ID_ALL, [Permission::VIEW, Permission::UPDATE, Permission::DELETE, Permission::LOCK]);
        $builder->add(Access::PROFILE_USER_READER, Access::ID_ALL, [Permission::VIEW]);

        // Is user is a user administrator, no one else than other users
        // administrators can modify it.
        if ($account->hasPermission(Access::ACL_PERM_MANAGE_USERS)) {
            return;
        }

        // If user is a single group member, group admins can manage it.
        $groupAccessList = $this->groupManager->getUserGroups($account);
        if (1 === count($groupAccessList)) {
            $groupAccess = reset($groupAccessList);
            $builder->add(Access::PROFILE_USER_GOD, $groupAccess->getGroupId(), [Permission::VIEW, Permission::UPDATE, Permission::DELETE, Permission::LOCK]);
        }

        // @todo - I'm afraid this one will be very, very heavy
        // If user is a single site member, webmasters can manage it.
    }

    /**
     * {@inheritdoc}
     */
    public function collectProfiles(ProfileSetBuilder $builder)
    {
        $account = $builder->getObject();
        if (!$account instanceof AccountInterface) {
            return;
        }

        // Platform wide permissions
        if ($account->hasPermission(Access::PERM_USER_MANAGE_ALL)) {
            $builder->add(Access::PROFILE_USER_GOD, Access::ID_ALL);
        } else if ($account->hasPermission(Access::PERM_USER_VIEW_ALL)) {
            $builder->add(Access::PROFILE_USER_READER, Access::ID_ALL);
        }

        // Per groups permissions
        foreach ($this->groupManager->getUserGroups($account) as $groupAccess) {
            if ($groupAccess->isGroupAdmin()) {
                $builder->add(Access::PROFILE_USER_GOD, $groupAccess->getGroupId());
            }
        }
    }
}
