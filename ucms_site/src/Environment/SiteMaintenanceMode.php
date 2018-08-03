<?php

namespace MakinaCorpus\Ucms\Site\Environment;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\MaintenanceModeInterface;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;

class SiteMaintenanceMode implements MaintenanceModeInterface
{
    private $siteManager;
    private $previous;

    /**
     * Default constructor
     */
    public function __construct(SiteManager $siteManager, MaintenanceModeInterface $previous)
    {
        $this->siteManager = $siteManager;
        $this->previous = $previous;
    }

    /**
     * {@inheritdoc}
     */
    public function applies(RouteMatchInterface $route_match)
    {
        if ($this->previous->applies($route_match)) {
            return true;
        }

        // We need to replicate this from parent implementation in order to
        // ensure that users can still access to login routes.
        // @todo later remove this piece of code, once login is handled by
        //   master only, like it is in Drupal 7 version.
        // @todo also allow the SSO login by adding the _maintenance_access
        //   on routes
        if ($route = $route_match->getRouteObject()) {
            if ($route->getOption('_maintenance_access')) {
                return FALSE;
            }
        }

        /*
         * @todo
         *   piece of code from Drupal 7, this needs some thinking...
         *
          if (SiteState::ON != $site->state && !ucms_site_manager()->getAccess()->user CanView(\Drupal::currentUser(), $site)) {
            $menu_site_status = MENU_SITE_OFFLINE;
            // State off means that the site is valid and up, but in maintainance mode,
            // case in which we should just set the maintainance mode and leave,
            // otherwise would mean that the site is neither ON nor OFF and does not
            // exist for the outside, so redirect to something that exists (the main
            // site). If we have nothing to redirect to, at least this code will
            // fallback on site being offline.
            if (SiteState::OFF != $site->state) {
              ucms_site_redirect_to_default();
            }
          }
         */

        return $this->siteManager->hasContext() && SiteState::ON !== $this->siteManager->getContext()->getState();
    }

    /**
     * {@inheritdoc}
     */
    public function exempt(AccountInterface $account)
    {
        return $this->siteManager->hasContext() &&
            $this->siteManager->getAccess()->userIsWebmaster($account, $this->siteManager->getContext())
        ;
    }
}
