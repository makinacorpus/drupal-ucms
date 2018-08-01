<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;

class SiteEventListener
{
    use StringTranslationTrait;

    private $manager;
    private $entityManager;

    /**
     * Default constructor
     */
    public function __construct(SiteManager $manager, EntityManager $entityManager)
    {
        $this->manager = $manager;
        $this->entityManager = $entityManager;
    }

    public function onSiteCreate(SiteEvent $event)
    {
        $site = $event->getSite();

        // Register the person that asked for the site as a webmaster while
        // skipping anonymous user
        if ($site->uid) {
            $this
                ->manager
                ->getAccess()
                ->addWebmasters($site, $site->uid)
            ;

            // User must inherit from the webmaster role when he does a request
            $storage = $this->entityManager->getStorage('user');

            /* @var $user UserInterface */
            if ($user = $storage->load($site->uid)) {
                $roles = $this->manager->getAccess()->getRolesAssociations();

                // Relative roles might not be set (this would an error thought)
                if ($rid = array_search(Access::ROLE_WEBMASTER, $roles)) {
                    if (!$user->hasRole($rid)) {
                        $user->addRole($rid);
                        $storage->save($user);
                    }
                }
            }
        }
    }


    public function onSiteSave(SiteEvent $event)
    {
        // @todo ?
    }


    public function onSiteSwitch(SiteEvent $event)
    {
        // If site is switching from PENDING to INIT and has a template
        if (
          $event->getArgument('from') == SiteState::PENDING && $event->getArgument('to') == SiteState::INIT
          && !$event->getSite()->is_template && $event->getSite()->template_id
        ) {
            $template = $this->manager->getStorage()->findOne($event->getSite()->template_id);
            // Clone the template site into the site
            $this->manager->getStorage()->duplicate($template, $event->getSite());
        }
    }


    public function onSiteWebmasterAddNew(SiteEvent $event)
    {
        $userStorage = $this->entityManager->getStorage('user');

        /* @var UserInterface $webmaster */
        $webmaster  = $userStorage->load($event->getArgument('webmaster_id'));
        $access     = $this->manager->getAccess()->getUserRole($webmaster, $event->getSite());
        $roles      = $this->manager->getAccess()->getRolesAssociations();

        // Relative roles might not be set (this would an error thought)
        if ($rid = array_search($access->getRole(), $roles)) {
            $webmaster->addRole($rid);
            $userStorage->save($webmaster);
        }
    }


    public function onSiteWebmasterAddExisting(SiteEvent $event)
    {
        $userStorage = $this->entityManager->getStorage('user');

        /* @var UserInterface $webmaster */
        $webmaster  = $userStorage->load($event->getArgument('webmaster_id'));
        $access     = $this->manager->getAccess()->getUserRole($webmaster, $event->getSite());
        $roles      = $this->manager->getAccess()->getRolesAssociations();

        // Relative roles might not be set (this would an error thought)
        if (($rid = array_search($access->getRole(), $roles)) && !$webmaster->hasRole($rid)) {
            $webmaster->addRole($rid);
            $userStorage->save($webmaster);
        }
    }


    public function onSiteWebmasterChangeRole(SiteEvent $event)
    {
        $userStorage = $this->entityManager->getStorage('user');

        /* @var UserInterface $user */
        $user   = $userStorage->load($event->getArgument('webmaster_id'));
        $roles  = $this->manager->getAccess()->getRolesAssociations();
        $access = $this->manager->getAccess()->getUserRole($user, $event->getSite());

        if (($rid = array_search($access->getRole(), $roles)) && !$user->hasRole($rid)) {
            $user->addRole($rid);
        }

        $deleteOldRole = true;
        $previousRoleId = $event->getArgument('previous_role');

        foreach ($this->manager->getAccess()->getUserRoles($user) as $access) {
            if (
                $access->getRole() == $previousRoleId &&
                $access->getSiteId() != $event->getSite()->id
            ) {
                $deleteOldRole = false;
                break;
            }
        }

        if ($deleteOldRole && ($rid = array_search($previousRoleId, $roles))) {
            $user->removeRole($rid);
        }

        $userStorage->save($user);
    }


    public function onSiteWebmasterPromote(SiteEvent $event)
    {
        $event->setArgument('previous_role', Access::ROLE_CONTRIB);
        $this->onSiteWebmasterChangeRole($event);
    }


    public function onSiteWebmasterDemote(SiteEvent $event)
    {
        $event->setArgument('previous_role', Access::ROLE_WEBMASTER);
        $this->onSiteWebmasterChangeRole($event);
    }


    public function onSiteWebmasterDelete(SiteEvent $event)
    {
        $userStorage = $this->entityManager->getStorage('user');

        /* @var UserInterface $webmaster */
        $webmaster  = $userStorage->load($event->getArgument('webmaster_id'));
        $roles      = $this->manager->getAccess()->getRolesAssociations();
        $role       = $event->getArgument('role');

        $deleteOldRole = true;

        foreach ($this->manager->getAccess()->getUserRoles($webmaster) as $access) {
            if ($access->getRole() == $role && $access->getSiteId() != $event->getSite()->id) {
                $deleteOldRole = false;
                break;
            }
        }

        if ($deleteOldRole && ($rid = array_search($role, $roles))) {
            $webmaster->removeRole($rid);
            $userStorage->save($webmaster);
        }
    }
}
