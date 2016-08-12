<?php


namespace MakinaCorpus\Ucms\Extranet\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Extranet\ExtranetAccess;
use MakinaCorpus\Ucms\Site\EventDispatcher\RolesCollectionEvent;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class RolesCollectionEventSubscriber implements EventSubscriberInterface
{
    use StringTranslationTrait;


    public static function getSubscribedEvents()
    {
        return [
            RolesCollectionEvent::EVENT_NAME => [
                ['onRolesCollection', 0]
            ],
        ];
    }


    public function onRolesCollection(RolesCollectionEvent $event)
    {
        if (!$event->hasContext() || !$event->getContext()->isPublic()) {
            $event->addRole(ExtranetAccess::ROLE_EXTRANET_CONTRIB, $this->t("Extranet contributor"));
            $event->addRole(ExtranetAccess::ROLE_EXTRANET_MEMBER, $this->t("Extranet member"));
        }
    }
}
