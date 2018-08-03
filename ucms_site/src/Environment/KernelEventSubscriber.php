<?php

namespace MakinaCorpus\Ucms\Site\Environment;

use Drupal\Core\Config\ConfigFactoryInterface;
use MakinaCorpus\Ucms\Site\SiteManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class KernelEventSubscriber implements EventSubscriberInterface
{
    private $configFactory;
    private $eventDispatcher;
    private $siteManager;

    /**
     * Default constructor
     */
    public function __construct(SiteManager $siteManager, EventDispatcherInterface $eventDispatcher, ConfigFactoryInterface $configFactory)
    {
        $this->configFactory = $configFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onRequest', 1024]
            ],
        ];
    }

    public function onRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        /*
        // Yes, this should also happen in Drush environment.
        if (!function_exists('drupal_path_initialize')) {
            $GLOBALS['conf']['path_inc'] = substr(__DIR__, strlen(realpath(DRUPAL_ROOT))) . '/../ucms_seo/includes/path.inc';
        }

        if (drupal_is_cli()) {
            return; // Make drush happy.
        }
         */

        $request = $event->getRequest();
        $hostname = $request->server->get('HTTP_HOST');

        if ($hostname) {
            $manager = $this->siteManager;

            if ($site = $manager->getStorage()->findByHostname($hostname)) {

                $manager->setContext($site, $request, true);

                // This has to be done before drupal_path_initialize() which is run right
                // before the hook_init(), so this will be the one and only alteration
                // being done on hook_boot().
                if ($site->hasHome()) {
                    $GLOBALS['conf']['site_frontpage'] = 'node/' . $site->home_nid;
                } else {
                    $GLOBALS['conf']['site_frontpage'] = 'node';
                }

            } else {
                if ($manager->getMasterHostname() === $hostname) {
                    $manager->setContextAsMaster($request);
                } else {
                    $manager->dropContext();

                    /* else if (!ucms_site_is_cdn()) {
                    // This will trigger the maintainance page.
                    ucms_site_fast_404();
                    } */
                }
            }
        } else {
            $manager->dropContext();
        }
    }
}
