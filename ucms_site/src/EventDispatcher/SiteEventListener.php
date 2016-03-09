<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use Drupal\Core\Entity\EntityManager;
use Drupal\user\UserInterface;

use MakinaCorpus\Ucms\Site\NodeDispatcher;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\SiteState;

class SiteEventListener
{
    /**
     * @var SiteManager
     */
    private $manager;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var NodeDispatcher
     */
    private $nodeDispatcher;


    /**
     * Default constructor
     *
     * @param SiteManager $manager
     * @param EntityManager $entityManager
     * @param NodeDispatcher $nodeDispatcher
     */
    public function __construct(SiteManager $manager, EntityManager $entityManager, NodeDispatcher $nodeDispatcher)
    {
        $this->manager = $manager;
        $this->entityManager = $entityManager;
        $this->nodeDispatcher = $nodeDispatcher;
    }


    public function onSiteCreate(SiteEvent $event)
    {
        $site = $event->getSite();

        if ($site->uid) { // Skips anonymous
            $this
                ->manager
                ->getAccess()
                ->addWebmasters($site, $site->uid)
            ;

            // User must inherit from the webmaster role when he does a request
            $storage = $this->entityManager->getStorage('user');

            /* @var $user UserInterface */
            if ($user = $storage->load($site->uid)) {
                $roles = $this->manager->getAccess()->getRelativeRoles();

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
            $this->nodeDispatcher->cloneSite($template, $event->getSite());
        }
    }


    public function onSiteWebmasterAddNew(SiteEvent $event)
    {
        $userStorage = $this->entityManager->getStorage('user');

        /* @var UserInterface $webmaster */
        $webmaster  = $userStorage->load($event->getArgument('webmaster_id'));
        $access     = $this->manager->getAccess()->getUserRole($webmaster, $event->getSite());
        $roles      = $this->manager->getAccess()->getRelativeRoles();

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
        $roles      = $this->manager->getAccess()->getRelativeRoles();

        // Relative roles might not be set (this would an error thought)
        if (($rid = array_search($access->getRole(), $roles)) && !$webmaster->hasRole($rid)) {
            $webmaster->addRole($rid);
            $userStorage->save($webmaster);
        }
    }


    public function onSiteWebmasterPromote(SiteEvent $event)
    {
        $userStorage = $this->entityManager->getStorage('user');

        /* @var UserInterface $webmaster */
        $webmaster  = $userStorage->load($event->getArgument('webmaster_id'));
        $roles      = $this->manager->getAccess()->getRelativeRoles();

        if (($rid = array_search(Access::ROLE_WEBMASTER, $roles)) && !$webmaster->hasRole($rid)) {
            $webmaster->addRole($rid);
        }

        $deleteContributorRole = true;
        
        foreach ($this->manager->getAccess()->getUserRoles($webmaster) as $access) {
            if ($access->getRole() == Access::ROLE_CONTRIB && $access->getSiteId() != $event->getSite()->id) {
                $deleteContributorRole = false;
                break;
            }
        }

        if ($deleteContributorRole && ($rid = array_search(Access::ROLE_CONTRIB, $roles))) {
            $webmaster->removeRole($rid);
        }

        $userStorage->save($webmaster);
    }


    public function onSiteWebmasterDemote(SiteEvent $event)
    {
        $userStorage = $this->entityManager->getStorage('user');

        /* @var UserInterface $webmaster */
        $webmaster  = $userStorage->load($event->getArgument('webmaster_id'));
        $roles      = $this->manager->getAccess()->getRelativeRoles();

        if (($rid = array_search(Access::ROLE_CONTRIB, $roles)) && !$webmaster->hasRole($rid)) {
            $webmaster->addRole($rid);
        }

        $deleteWebmasterRole = true;

        foreach ($this->manager->getAccess()->getUserRoles($webmaster) as $access) {
            if ($access->getRole() == Access::ROLE_WEBMASTER && $access->getSiteId() != $event->getSite()->id) {
                $deleteWebmasterRole = false;
                break;
            }
        }

        if ($deleteWebmasterRole && ($rid = array_search(Access::ROLE_WEBMASTER, $roles))) {
            $webmaster->removeRole($rid);
        }

        $userStorage->save($webmaster);
    }


    public function onSiteWebmasterDelete(SiteEvent $event)
    {
        $userStorage = $this->entityManager->getStorage('user');

        /* @var UserInterface $webmaster */
        $webmaster  = $userStorage->load($event->getArgument('webmaster_id'));
        $roles      = $this->manager->getAccess()->getRelativeRoles();
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
