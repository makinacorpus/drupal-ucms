<?php

namespace MakinaCorpus\Ucms\Site\Environment;

use Drupal\Core\Routing\RouteProvider;
use MakinaCorpus\Ucms\Site\Site;
use Symfony\Component\HttpFoundation\Request;

/**
 * This will extend the Drupal route provider in order to build cache
 * identifiers aware of site identifier.
 *
 * @todo explore by disabling completely caching instead, this will make the
 *   cache volume explode because query string is part of the cache, and this
 *   may lead to DDoS, open core issue
 */
class SiteRouteProvider extends RouteProvider
{
    /**
     * {@inheritdoc}
     */
    protected function getRouteCollectionCacheId(Request $request)
    {
        $language_part = $this->getCurrentLanguageCacheIdPart();

        if ($site = Site::fromRequest($request)) {
            $language_part .= ':'.$site->getId();
        }

        return 'route:'.$language_part.':'.$request->getPathInfo().':'.$request->getQueryString();
    }
}
