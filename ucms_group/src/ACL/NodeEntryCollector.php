<?php

namespace MakinaCorpus\Ucms\Group\ACL;

use Drupal\Core\Session\AccountInterface;
use MakinaCorpus\ACL\Collector\ProfileCollectorInterface;
use MakinaCorpus\ACL\Collector\ProfileSetBuilder;
use MakinaCorpus\Ucms\Group\GroupManager;
use MakinaCorpus\Ucms\Site\Access;

final class NodeEntryCollector implements ProfileCollectorInterface
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
     * Fetch the list of realms this module modifies
     *
     * @return string[]
     */
    private function getAlteredProfiles()
    {
        return [
            Access::PROFILE_GLOBAL,
            Access::PROFILE_GLOBAL_READONLY,
            Access::PROFILE_CORPORATE_ADMIN,
            Access::PROFILE_CORPORATE_READER,
            Access::PROFILE_OTHER,
            Access::PROFILE_READONLY,
        ];
    }

    /**
     * Collect entries for resource
     *
     * @param ProfileSetBuilder $builder
     */
    public function collectProfiles(ProfileSetBuilder $builder)
    {
        $account = $builder->getObject();
        if (!$account instanceof AccountInterface) {
            return;
        }

        // Some users have global permissions on the platform, we need to give
        // them the right to see orphan content.
        if ($account->hasPermission(Access::PERM_GROUP_MANAGE_ORPHAN)) {
            $builder->add(Access::PROFILE_GROUP_ORPHAN_READER, Access::ID_ALL);
        }

        // Then replicate all user permissions, but relative to groups.
        foreach ($this->groupManager->getAccess()->getUserGroups($account) as $access) {

            /** @var \MakinaCorpus\Ucms\Group\GroupMember $access */
            $groupId = $access->getGroupId();
            // @todo view all permission is global
            $viewAll = $account->hasPermission(Access::PERM_CONTENT_VIEW_ALL);

            if ($viewAll) {
                $builder->add(Access::PROFILE_READONLY, $groupId);
            }

            if ($account->hasPermission(Access::PERM_CONTENT_MANAGE_GLOBAL)) {
                $builder->add(Access::PROFILE_GLOBAL, $groupId);
            }
            if ($account->hasPermission(Access::PERM_CONTENT_MANAGE_CORPORATE)) {
                $builder->add(Access::PROFILE_CORPORATE_ADMIN, $groupId);
            }

            if ($account->hasPermission(Access::PERM_CONTENT_VIEW_ALL)) {
                $builder->add(Access::PROFILE_READONLY, $groupId);
            } else {
                if ($account->hasPermission(Access::PERM_CONTENT_VIEW_GLOBAL)) {
                    $builder->add(Access::PROFILE_GLOBAL_READONLY, $groupId);
                }
                if ($account->hasPermission(Access::PERM_CONTENT_VIEW_CORPORATE)) {
                    $builder->add(Access::PROFILE_CORPORATE_READER, $groupId);
                }
                if ($account->hasPermission(Access::PERM_CONTENT_VIEW_OTHER)) {
                    $builder->add(Access::PROFILE_OTHER, $groupId);
                }
            }
        }
    }
}
