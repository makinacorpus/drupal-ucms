<?php

namespace MakinaCorpus\Ucms\Site\Controller;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;

class UtilityController extends ControllerBase
{
    public function goToSite($siteId)
    {
        $url = new Url('<front>', [], [
            'absolute' => true,
            'ucms_site' => $siteId,
            'ucms_sso' => $this->currentUser()->isAuthenticated(),
        ]);

        // @todo
        //   What the fuck Drupal, seriously.
        // @see
        //   - https://www.drupal.org/node/2630808
        //   - https://drupal.stackexchange.com/questions/225956/cache-controller-with-json-response
        //   - ... and many others.
        //
        // Epic developer experience fail, this is terrible, one hour of debug
        // to finally randomly finding the correct solution because the trusted
        // redirect responses are not treated the same way as the "normal" ones.
        $url = $url->toString(true)->getGeneratedUrl();

        $response = new TrustedRedirectResponse($url);
        $response->setPrivate();
        $response->setVary(['Cookie']);

        // @todo Drupal 8 does not honnor programatically set 'private'
        //   cache control value, in DynamicPageCacheSubscriber it will
        //   just set dynamic cache to on. Following lines prevent that.
        $cache = $response->getCacheableMetadata();
        $cache->setCacheMaxAge(0);

        return $response;
    }
}
