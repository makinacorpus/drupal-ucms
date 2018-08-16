<?php

namespace MakinaCorpus\Ucms\Site\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;

class UtilityController extends ControllerBase
{
    public function goToSite($siteId)
    {
        $url = new Url('<front>', [], [
            'absolute' => true,
            'ucms_site' => $siteId,
            'ucms_sso' => $this->currentUser()->isAuthenticated(),
        ]);

        // What the fuck Drupal, seriously.
        // @see
        //   https://www.drupal.org/node/2630808
        //   https://drupal.stackexchange.com/questions/225956/cache-controller-with-json-response
        //   ... and many others.
        throw new \Exception(\sprintf("Sorry, Drupal can't handle %s as a controller response, and there is no solution here.", TrustedRedirectResponse::class));

        return new TrustedRedirectResponse($url->toString(true));
    }
}
