<?php


namespace MakinaCorpus\Ucms\Extranet\EventDispatcher;

use MakinaCorpus\Ucms\Extranet\ExtranetAccess;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteStatusEvent;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class SiteStatusEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var SiteManager
     */
    protected $manager;


    public static function getSubscribedEvents()
    {
        return [
            SiteStatusEvent::EVENT_NAME => [
                ['onSiteStatusAlter', 0]
            ],
        ];
    }


    /**
     * Constructor.
     *
     * @param SiteManager $manager
     */
    public function __construct(SiteManager $manager)
    {
        $this->manager = $manager;
    }


    public function onSiteStatusAlter(SiteStatusEvent $event)
    {
        if ($event->getSite()->isPublic()) {
            return;
        }

        $user = \Drupal::currentUser();

        $valid_paths = implode(PHP_EOL, [
            'extranet/register',
            'extranet/register/confirm',
            'set-password/*',
            'sso/login',
            'user/login',
        ]);

        if (
            !drupal_match_path($event->getPath(), $valid_paths) &&
            !$user->hasPermission(ExtranetAccess::PERM_EXTRANET_ACCESS_ALL) &&
            !$this->manager->getAccess()->userHasRole($user, $event->getSite())
        ) {
            $event->setStatus(MENU_ACCESS_DENIED);
        }
    }
}
