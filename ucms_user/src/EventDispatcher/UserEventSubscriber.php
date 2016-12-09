<?php

namespace MakinaCorpus\Ucms\User\EventDispatcher;

use Drupal\Core\Entity\EntityManager;

use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvents;
use MakinaCorpus\Ucms\User\TokenManager;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserEventSubscriber implements EventSubscriberInterface
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
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            SiteEvents::EVENT_WEBMASTER_CREATE => [
                ['onSiteWebmasterCreate', 0],
            ],
        ];
    }

    /**
     * Act on webmaster or contributor creation.
     *
     * @param SiteEvent $event
     */
    public function onSiteWebmasterCreate(SiteEvent $event)
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
