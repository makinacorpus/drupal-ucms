<?php

namespace MakinaCorpus\Ucms\Dashboard;

use Drupal\Core\Extension\ModuleHandlerInterface;

use MakinaCorpus\APubSub\Notification\NotificationService;
use MakinaCorpus\Ucms\Site\Site;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service that implements the µCMS domain specific language and sends
 * notification upon events that are being raised; this facade also allows
 * to deactivate the 'ucms_notification' module without having any side
 * effects.
 *
 * @todo
 *   consider using the event dispatcher instead of doing a facade and
 *   simplify the apubsub/notification UI
 */
class NotificationFacade
{
    /**
     * @var NotificationService
     */
    private $service;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ModuleHandlerInterface
     */
    private $moduleHandler;

    /**
     * Default constructor
     *
     * @param ContainerInterface $container
     * @param ModuleHandlerInterface $moduleHandler
     */
    public function __construct(ContainerInterface $container, ModuleHandlerInterface $moduleHandler)
    {
        $this->container = $container;
        $this->moduleHandler = $moduleHandler;
    }

    /**
     * Get real notification service
     *
     * @return \MakinaCorpus\APubSub\Notification\NotificationService
     */
    private function getService()
    {
        if (null === $this->service) {
            if ($this->moduleHandler->moduleExists('ucms_notification')) {
                $this->service = $this->container->get('apb.notification');
            } else {
                $this->service = false;
            }
        }

        return $this->service;
    }

    private function subscribe($account, $type, $id)
    {
        if ($service = $this->getService()) {
            $service
                ->subscribe(
                    $service->getChanId($type, $id),
                    $service->getSubscriberName('u', $account->uid)
                )
            ;
        }
    }

    private function unsubscribe($account, $type, $id)
    {
        if ($service = $this->getService()) {
            $service
                ->unsubscribe(
                    $service->getChanId($type, $id),
                    $service->getSubscriberName('u', $account->uid)
                )
            ;
        }
    }

    private function deleteChannel($type, $id)
    {
        if ($service = $this->getService()) {
            $service
                ->getBackend()
                ->deleteChannel(
                    $service->getChanId($type, $id),
                    true
                )
            ;
        }
    }

    public function siteStateChanged(Site $site, $state, $previous, $account = null)
    {
        if ($service = $this->getService()) {
            $service
                ->notify(
                    $service->getChanId('site', $site->id),
                    'site_state_changed',
                    [
                        'id'        => $site->id,
                        'state'     => $state,
                        'previous'  => $previous,
                        'uid'       => $account->uid,
                    ]
                )
            ;
        }
    }

    public function subscribeToSite($account, Site $site)
    {
        $this->subscribe($account, 'site', $site->id);
    }

    public function unsubscribeToSite($account, $siteId)
    {
        $this->unsubscribe($account, 'site', $siteId);
    }

    public function siteDeleted($siteId)
    {
        $this->deleteChannel('site', $siteId);
    }

    public function subscribeToSiteAdmin($account)
    {
        $this->subscribe($account, 'site_admin', 0);
    }

    public function unsubscribeToSiteAdmin($account)
    {
        $this->unsubscribe($account, 'site_admin', 0);
    }

    public function subscribeToNode($account, $node)
    {
        $this->subscribe($account, 'node', $node->nid);
    }

    public function unsubscribeToNode($account, $nodeId)
    {
        $this->unsubscribe($account, 'node', $nodeId);
    }

    public function nodeDeleted($nodeId)
    {
        $this->deleteChannel('node', $nodeId);
    }
}
