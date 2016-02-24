<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use Drupal\Core\Entity\EntityManager;
use Drupal\user\UserInterface;

use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\Access;

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
     * Default constructor
     *
     * @param SiteManager $manager
     */
    public function __construct(SiteManager $manager, EntityManager $entityManager)
    {
        $this->manager = $manager;
        $this->entityManager = $entityManager;
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
}
