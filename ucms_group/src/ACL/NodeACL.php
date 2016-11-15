<?php

namespace MakinaCorpus\Ucms\Group\ACL;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

use MakinaCorpus\ACL\Collector\EntryCollectorInterface;
use MakinaCorpus\ACL\Collector\EntryListBuilderInterface;
use MakinaCorpus\ACL\Collector\ProfileCollectorInterface;
use MakinaCorpus\ACL\Collector\ProfileSetBuilder;
use MakinaCorpus\ACL\Manager;
use MakinaCorpus\ACL\Permission;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Group\GroupAccess;

final class NodeACL implements EntryCollectorInterface, ProfileCollectorInterface
{
    private $entityManager;

    /**
     * Default constructor
     *
     * @param SiteManager $manager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, $permission)
    {
        return 'node' === $type && in_array($permission, [Permission::VIEW, Permission::UPDATE, Permission::DELETE]);
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
            Access::PROFILE_GROUP,
            Access::PROFILE_GROUP_READONLY,
            Access::PROFILE_OTHER,
            Access::PROFILE_READONLY,
        ];
    }

    /**
     * Collect entries for resource
     *
     * @param EntryListBuilderInterface $entries
     */
    public function collectEntryLists(EntryListBuilderInterface $builder)
    {
        $resource = $builder->getResource();

        if ('node' !== $resource->getType()) {
            // @todo warn or fail?
            return;
        }

        $node = $builder->getObject();
        if (!$node instanceof NodeInterface) {
            $node = $this->entityManager->getStorage('node')->load($resource->getId());
            if (!$node) {
                return;
            }
        }

        $readOnly = [Permission::VIEW];
        $alteredRealms = $this->getAlteredProfiles();

        if (!empty($node->group_id)) {

            // We will re-use the realms from 'ucms_site' but changing the default
            // gid to group identifiers instead, and make the whole isolation thing
            // completly transparent. Ghost nodes cannot be seen in global realms,
            // so we are just going to replace their realm identifiers using the
            // ones from the group it's in.
            $builder->relocateProfile(Access::ID_ALL, $node->group_id, $alteredRealms);

            if (!$node->is_ghost && $node->isPublished()) {

                // But sadly, non ghost nodes should be seen outside, so we
                // actually do need to restore them rights, at least in
                // readonly mode. Please see how the 'ucms_site' module sets
                // them to understand, we are only going to deal with readonly
                // rights.
                $builder->add(Access::PROFILE_READONLY, Access::ID_ALL, $readOnly);

                // This handles two grants in one:
                //  - Webmasters can browse along published content of other sites
                //  - People with global repository access may see this content
                if ($node->is_group) {
                    $builder->add(Access::PROFILE_GROUP_READONLY, Access::ID_ALL, $readOnly);
                } else if ($node->is_global) {
                    $builder->add(Access::PROFILE_GLOBAL_READONLY, Access::ID_ALL, $readOnly);
                } else {
                    $builder->add(Access::PROFILE_OTHER, Access::ID_ALL, $readOnly);
                }
            }
        } else {
            foreach ($alteredRealms as $type) {
                $builder->remove($type);
            }

            // This node cannot be seen anywhere, we just give the global
            // platform administrators the right to see it
            return $builder->add(GroupAccess::PROFILE_GROUP_ORPHAN, Access::ID_ALL, $readOnly);
        }
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

        // God mode.
        if ($account->hasPermission(Access::PERM_CONTENT_GOD)) {
            return;
        }

        // Some users have global permissions on the platform, we need to give
        // them the right to see orphan content.
        if ($account->hasPermission(GroupAccess::PERM_MANAGE_ORPHAN)) {
            $builder->add(GroupAccess::PROFILE_GROUP_ORPHAN, Access::ID_ALL);
        }

        // Note that we won't change anything about site rights.

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
            if ($account->hasPermission(Access::PERM_CONTENT_MANAGE_GROUP)) {
                $builder->add(Access::PROFILE_GROUP, $groupId);
            }

            if ($account->hasPermission(Access::PERM_CONTENT_VIEW_ALL)) {
                $builder->add(Access::PROFILE_READONLY, $groupId);
            } else {
                if ($account->hasPermission(Access::PERM_CONTENT_VIEW_GLOBAL)) {
                    $builder->add(Access::PROFILE_GLOBAL_READONLY, $groupId);
                }
                if ($account->hasPermission(Access::PERM_CONTENT_VIEW_GROUP)) {
                    $builder->add(Access::PROFILE_GROUP_READONLY, $groupId);
                }
                if ($account->hasPermission(Access::PERM_CONTENT_VIEW_OTHER)) {
                    $builder->add(Access::PROFILE_OTHER, $groupId);
                }
            }
        }
    }
}
