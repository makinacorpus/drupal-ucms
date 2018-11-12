<?php

namespace MakinaCorpus\Ucms\Seo\EventDispatcher;

use MakinaCorpus\Ucms\Seo\SeoService;
use MakinaCorpus\Ucms\Site\EventDispatcher\MasterInitEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvents;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteInitEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This subscriber will collect linked content within text fields.
 */
class SiteEventSubscriber implements EventSubscriberInterface
{
    private $service;
    private $dispatcher;

    /**
     * Default constructor
     */
    public function __construct(SeoService $service, EventDispatcherInterface $dispatcher)
    {
        $this->service = $service;
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            SiteEvents::EVENT_INIT => [
                ['onInit', 0]
            ],
            SiteEvents::EVENT_MASTER_INIT => [
                ['onMasterInit', 0]
            ],
            KernelEvents::TERMINATE => [
                ['onTerminate', 0]
            ],
        ];
    }

    /**
     * Site is being initialized, and hopefully at this stage current menu
     * item has never been loaded, so alter the current path to something
     * else, before Drupal router gets to us.
     */
    public function onInit(SiteInitEvent $event)
    {
        // Naive alias lookup for the current page
        $path       = null;
        $site       = $event->getSite();
        $request    = $event->getRequest();
        $incomming  = $request->query->get('q');

        $nodeId = $this->service->getAliasManager()->matchPath($incomming, $site->getId());
        if ($nodeId) {
            $path = $incomming = 'node/' . $nodeId;
        } else {
            $event = new PathMatchEvent($site, $incomming);
            $this->dispatcher->dispatch(PathMatchEvent::EVENT_NAME, $event);
            $path = $event->getMatchedPath(); // It still can be null.
        }

        if ($path) {
            // $_GET['q'] reference will be useless for Drupal 8
            $_GET['q'] = $path;
            $request->query->set('q', $incomming);
            $request->attributes->set('_route', $incomming);
        }

        // Set current context to the alias manager
        $this
            ->service
            ->getAliasCacheLookup()
            ->setEnvironment(
                $site->getId(),
                $incomming,
                $request->query->all()
            )
        ;
    }

    /**
     * We have no site context, we are in admin.
     */
    public function onMasterInit(MasterInitEvent $event)
    {
        $request = $event->getRequest();

        $this
            ->service
            ->getAliasCacheLookup()
            ->setEnvironment(
                null,
                $request->query->get('q'),
                $request->query->all()
            )
        ;
    }

    /**
     * On terminate write cache.
     */
    public function onTerminate()
    {
        $this->service->getAliasCacheLookup()->write();
    }
}
