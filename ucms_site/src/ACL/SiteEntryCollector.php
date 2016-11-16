<?php

namespace MakinaCorpus\Ucms\Site\ACL;

use Drupal\Core\Session\AccountInterface;

use MakinaCorpus\ACL\Collector\EntryCollectorInterface;
use MakinaCorpus\ACL\Collector\EntryListBuilderInterface;
use MakinaCorpus\ACL\Collector\ProfileCollectorInterface;
use MakinaCorpus\ACL\Collector\ProfileSetBuilder;
use MakinaCorpus\ACL\Converter\ResourceConverterInterface;
use MakinaCorpus\ACL\Manager;
use MakinaCorpus\ACL\Permission;
use MakinaCorpus\ACL\Resource;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;

final class SiteEntryCollector implements EntryCollectorInterface, ProfileCollectorInterface, ResourceConverterInterface
{
    private $siteManager;

    /**
     * Default constructor
     *
     * @param SiteManager $manager
     */
    public function __construct(SiteManager $siteManager)
    {
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

        $siteId = $site->getId();

        // No matter the site state, admins can view them all.
        $builder->add(Access::PROFILE_SITE_ADMIN, Access::ID_ALL, [Permission::OVERVIEW, Permission::UPDATE, Access::ACL_PERM_SITE_MANAGE_USERS]);
        $builder->add(Access::PROFILE_SITE_ADMIN_RO, Access::ID_ALL, [Permission::OVERVIEW]);

        // Sites cannot be viewed if they have not been created.
        if (SiteState::INIT <= $site->state) {
            $builder->add(Access::PROFILE_SITE_ADMIN, Access::ID_ALL, [Permission::VIEW]);
            $builder->add(Access::PROFILE_SITE_ADMIN_RO, Access::ID_ALL, [Permission::VIEW]);
        }

        // No matter the state, webmasters can always see site information
        // in adminitration interface; they need to be able to follow the
        // validation workflow from the site creation upon its death
        $builder->add(Access::PROFILE_SITE_WEBMASTER, $siteId, [Permission::OVERVIEW, Access::ACL_PERM_SITE_MANAGE_USERS]);

        switch ($site->state) {

            case SiteState::ON:
            case SiteState::OFF:
                $builder->add(Access::PROFILE_SITE_WEBMASTER, $siteId, [Permission::VIEW, Permission::UPDATE]);
                $builder->add(Access::PROFILE_SITE_READONLY, $siteId, [Permission::VIEW, Permission::OVERVIEW]);
                break;

            case SiteState::INIT:
                $builder->add(Access::PROFILE_SITE_WEBMASTER, $siteId, [Permission::VIEW, Permission::UPDATE]);
                break;

            case SiteState::ARCHIVE:
                $builder->add(Access::PROFILE_SITE_WEBMASTER, $siteId, [Permission::VIEW]);
                // Sites can be deleted only when they are archived first.
                // This is a security that will prevent any accidental site
                // deletion.
                $builder->add(Access::PROFILE_SITE_ADMIN, $siteId, [Permission::DELETE]);
                break;
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

        if ($account->hasPermission(Access::PERM_SITE_VIEW_ALL)) {
            $builder->add(Access::PROFILE_SITE_ADMIN_RO, Access::ID_ALL);
        }
        if ($account->hasPermission(Access::PERM_SITE_MANAGE_ALL) || $account->hasPermission(Access::PERM_SITE_GOD)) {
            $builder->add(Access::PROFILE_SITE_ADMIN, Access::ID_ALL);
        }
    }
}
