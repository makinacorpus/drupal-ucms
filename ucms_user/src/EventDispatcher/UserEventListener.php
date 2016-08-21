<?php


namespace MakinaCorpus\Ucms\User\EventDispatcher;

use Drupal\Core\Entity\EntityManager;

use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\User\TokenManager;


class UserEventListener
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var TokenManager
     */
    private $tokenManager;


    /**
     * Default constructor
     *
     * @param EntityManager $entityManager
     * @param TokenManager $tokenManager
     */
    public function __construct(EntityManager $entityManager, TokenManager $tokenManager)
    {
        $this->entityManager = $entityManager;
        $this->tokenManager = $tokenManager;
    }


    /**
     * Act on webmaster or contributor creation.
     *
     * @param SiteEvent $event
     */
    public function onSiteWebmasterAddNew(SiteEvent $event)
    {
        /* @var UserInterface $user */
        $user = $this->entityManager->getStorage('user')->load($event->getArgument('webmaster_id'));

        if ($user->status == 0) {
            $this->tokenManager->sendTokenMail($user, 'ucms_user', 'new-account-disabled');
        } else {
            $this->tokenManager->sendTokenMail($user, 'ucms_user', 'new-account-enabled');
        }
    }
}
