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
use MakinaCorpus\Ucms\Site\Group;
use MakinaCorpus\Ucms\Site\GroupManager;

final class GroupEntryCollector implements EntryCollectorInterface, ProfileCollectorInterface, ResourceConverterInterface
{
    private $groupManager;

    /**
     * Default constructor
     */
    public function __construct(GroupManager $groupManager)
    {
        $this->groupManager = $groupManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $type, string $permission) : bool
    {
        return 'group' === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsType(string $type) : bool
    {
        return 'group' === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function convert($object)
    {
        if ($object instanceof Group) {
            return new Resource('group', $object->getId());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function collectEntryLists(EntryListBuilder $builder)
    {
        $resource = $builder->getResource();
        $groupId = $resource->getId();

        $builder->add(Access::PROFILE_GROUP_GOD, Access::ID_ALL, [Permission::VIEW, Permission::UPDATE, Permission::DELETE, Access::ACL_PERM_MANAGE_USERS]);
        $builder->add(Access::PROFILE_GROUP_ADMIN, $groupId, [Permission::VIEW, Access::ACL_PERM_MANAGE_USERS]);
        $builder->add(Access::PROFILE_GROUP_MEMBER, $groupId, [Permission::VIEW]);
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

        if ($account->hasPermission(Access::PERM_GROUP_MANAGE_ALL)) {
            $builder->add(Access::PROFILE_GROUP_GOD, Access::ID_ALL);
        }

        foreach ($this->groupManager->getUserGroups($account) as $access) {
            $groupId = $access->getGroupId();
            $builder->add(Access::PROFILE_GROUP_MEMBER, $groupId);
            if ($access->isGroupAdmin()) {
                $builder->add(Access::PROFILE_GROUP_ADMIN, $groupId);
            }
        }
    }
}
