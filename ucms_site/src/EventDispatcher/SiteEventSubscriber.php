<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SiteEventSubscriber implements EventSubscriberInterface
{
    use StringTranslationTrait;

    private $manager;

    /**
     * Default constructor
     */
    public function __construct(SiteManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            SiteEvents::EVENT_CREATE => [
                ['onSiteCreate', 0],
            ],
            SiteEvents::EVENT_SWITCH => [
                ['onSiteSwitch', 0],
            ],
        ];
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
        }
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
}
