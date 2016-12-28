<?php

namespace MakinaCorpus\Ucms\Contrib\EventDispatcher;

use Drupal\Core\Extension\ModuleHandler;

use MakinaCorpus\Ucms\Contrib\EventDispatcher\BehaviorCollectionEvent;
use MakinaCorpus\Ucms\Contrib\Behavior\ContentTypeBehavior as Behavior;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BehaviorCollectionEventSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            BehaviorCollectionEvent::EVENT_NAME => [
                ['onCollect', 0],
            ],
        ];
    }

    /**
     * @param BehaviorCollectionEvent $event
     */
    public function onCollect(BehaviorCollectionEvent $event)
    {
        $event->addBehavior((new Behavior('appear_as_editorial'))
            ->setName("Apparaître en tant que type de contenu éditorial")
            ->setDescription("Les contenus du type seront listés dans la section des contenus éditoriaux." . PHP_EOL
                . "Le bouton d'accès au formulaire d'édition sera disponible dans cette même section.")
        );

        $event->addBehavior((new Behavior('appear_as_media'))
            ->setName("Apparaître en tant que type de contenu média")
            ->setDescription("Les contenus du type seront listés dans la section des médias." . PHP_EOL
                . "Le bouton d'accès au formulaire d'édition sera disponible dans cette même section.")
        );

        $event->addBehavior((new Behavior('appear_as_component'))
            ->setName("Apparaître en tant que type de contenu composant")
            ->setDescription("Semblable à \"Apparaître en tant que type de contenu éditorial\""
                . " excepté le bouton d'accès au formulaire d'édition visuellement"
                . " séparé de ceux concernant les contenus éditoriaux.")
        );

        $event->addBehavior((new Behavior('droppable_in_wysiwyg'))
            ->setName("Peut être glissé-déposé dans les champs wysiwyg")
            ->setDescription("Les contenus pourront être glissés-déposés du panier vers les zones d'édition de texte wysiwyg.")
        );

        $event->addBehavior((new Behavior('singleton'))
            ->setName("Singleton")
            ->setDescription("Les contenus du type sont uniques à chaque site.")
        );

        $event->addBehavior((new Behavior('locked'))
            ->setName("Type de contenu verrouillé")
            ->setDescription("Les contenus du type ne peuvent être créés manuellement via l'interface d'administration.")
        );
    }
}
