<?php

namespace MakinaCorpus\Ucms\Group\ACL;

use Drupal\Core\Session\AccountInterface;
use MakinaCorpus\ACL\Resource;
use MakinaCorpus\ACL\Collector\EntryCollectorInterface;
use MakinaCorpus\ACL\Collector\EntryListBuilderInterface;
use MakinaCorpus\ACL\Collector\ProfileCollectorInterface;
use MakinaCorpus\ACL\Collector\ProfileSetBuilder;
use MakinaCorpus\ACL\Converter\ResourceConverterInterface;
use MakinaCorpus\Ucms\Group\GroupManager;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;

final class SiteEntryCollector implements EntryCollectorInterface, ProfileCollectorInterface, ResourceConverterInterface
{
    private $groupManager;
    private $siteManager;

    /**
     * Default constructor
     */
    public function __construct(GroupManager $groupManager, SiteManager $siteManager)
    {
        $this->groupManager = $groupManager;
        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, $permission)
    {
        return 'site' === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsType($type)
    {
        return 'site' === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function convert($object)
    {
        if ($object instanceof Site) {
            return new Resource('site', $object->getId());
        }
    }

    /**
     * Collect entries for resource
     *
     * @param EntryListBuilderInterface $entries
     */
    public function collectEntryLists(EntryListBuilderInterface $builder)
    {
        $resource = $builder->getResource();
        $site = $builder->getObject();

        if (!$site instanceof Site) {
            $site = $this->siteManager->getStorage()->findOne($resource->getId());
            if (!$site) {
                return;
            }
        }

        // When site has a group, relocate all site wide permissions to
        // group wide permissions, thus isolating it within the group.
        if ($site->group_id) {
            $builder->relocateProfile(Access::ID_ALL, $site->group_id, [
                Access::PROFILE_SITE_ADMIN,
                Access::PROFILE_SITE_ADMIN_RO,
            ]);
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

        // Remove all out-of-group global permissions
        $builder->remove(Access::PROFILE_SITE_ADMIN_RO, Access::ID_ALL);
        $builder->remove(Access::PROFILE_SITE_ADMIN, Access::ID_ALL);

        foreach ($this->groupManager->getAccess()->getUserGroups($account) as $access) {

            /** @var \MakinaCorpus\Ucms\Group\GroupMember $access */
            $groupId = $access->getGroupId();

            if ($account->hasPermission(Access::PERM_SITE_VIEW_ALL)) {
                $builder->add(Access::PROFILE_SITE_ADMIN_RO, $groupId);
            }
            if ($account->hasPermission(Access::PERM_SITE_MANAGE_ALL)) {
                $builder->add(Access::PROFILE_SITE_ADMIN, $groupId);
            }
        }
    }
}
