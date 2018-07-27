<?php

namespace MakinaCorpus\Ucms\Site\Environment;

use Drupal\Core\Routing\UrlGeneratorInterface;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Deals with platform wide inter-sites URL generation.
 *
 * @todo this class must die, and should be implemetned as a decorator
 *   arround the Drupal default URL generator instead
 * @todo rework destination parameter handling correctly
 */
class CrossSiteUrlGenerator implements UrlGeneratorInterface
{
    private $nested;
    private $routeProvider;
    private $siteManager;
    private $ssoEnabled = false; // @todo fixme later

    /**
     * Default constructor
     */
    public function __construct(SiteManager $siteManager, UrlGeneratorInterface $nested, RouteProviderInterface $routeProvider)
    {
        $this->nested = $nested;
        $this->routeProvider = $routeProvider;
        $this->siteManager = $siteManager;

        // @todo sad hack, we cannot use module_exists() because this will be
        //   instanciated during hook_boot() and modules are not yet been
        //   activated
        // $this->ssoEnabled = module_exists('ucms_sso');
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->nested->setContext($context);
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        return $this->generateFromRoute($name, $parameters, [
            'absolute' => is_bool($referenceType) ? $referenceType : $referenceType === self::ABSOLUTE_URL,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->nested->getContext();
    }

    /**
     * {@inheritdoc}
     */
    public function supports($name)
    {
        return $this->nested->supports($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteDebugMessage($name, array $parameters = [])
    {
        return $this->nested->getRouteDebugMessage($name, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getPathFromRoute($name, $parameters = [])
    {
        return $this->nested->getPathFromRoute($name, $parameters);
    }

    /**
     * Is route allowed on site
     */
    private function isRouteAllowedOnSite(string $route): bool
    {
        if (!$this->routeProvider) {
            return true;
        }

        // @todo allow node edit routes

        return !$this->routeProvider->getRouteByName($route)->hasOption('_admin_route');
    }

    /**
     * Is route allowed on master
     */
    private function isRouteAllowedInMaster(string $route): bool
    {
        if (!$this->routeProvider) {
            return true;
        }

        return $this->routeProvider->getRouteByName($route)->hasOption('_admin_route');
    }

    /**
     * {@inheritdoc}
     */
    public function generateFromRoute($name, $parameters = [], $options = [], $collectBubbleableMetadata = false)
    {
        $manager = $this->siteManager;

        // With no master hostname, there is nothing that can be done,
        // environment is misconfigured.
        if (!$masterHostname = $this->siteManager->getMasterHostname()) {
            return $this->nested->generateFromRoute($name, $parameters, $options, $collectBubbleableMetadata);
        }

        // Undocumented if null is allowed or not, better be safe than sorry.
        $options = $options ?? [];

        if ($site = $options['ucms_site'] ?? null) {
            if (!$site instanceof Site) {
                // @todo should we catch exceptions and be resilient to errors here?
                $site = $manager->getStorage()->findOne($options['ucms_site']);
            }
        }

        if ($site) {
            // When a site is given, force it into the URL and pass.
            if (Site::ALLOWED_PROTOCOL_PASS !== $site->getAllowedProtocols()) {
                if ($options['https'] = $site->isHttpsAllowed()) {
                    $options['base_url'] = 'https://'.$site->getHostname();
                } else {
                    $options['base_url'] = 'http://'.$site->getHostname();
                }
            }

            return $this->nested->generateFromRoute($name, $parameters, $options, $collectBubbleableMetadata);

        } else if ($manager->hasContext()) {

            // Master allowed URLs must go to master.
            if (!$this->isRouteAllowedOnSite($name)) {
                if ($options['https'] = $manager->isMasterHttps()) {
                    $options['base_url'] = 'https://'.$masterHostname;
                } else {
                    $options['base_url'] = 'http://'.$masterHostname;
                }

                return $this->nested->generateFromRoute($name, $parameters, $options, $collectBubbleableMetadata);
            }

            // Pass.
            return $this->nested->generateFromRoute($name, $parameters, $options, $collectBubbleableMetadata);

        } else {
            // @todo check for route node, and redirect to suitable site
            //   for redirecting
        }

        return $this->nested->generateFromRoute($name, $parameters, $options, $collectBubbleableMetadata);
    }

    /**
     * Is the given path allowed in sites
     *
     * @todo for now, it's hardcoded.
     *
    public function isPathAllowedOnSite(string $path): bool
    {
        // Proceed to node path check first: most URL will always be node URL
        // we must shortcut them as quicly as possible to gain a few CPU cycles
        // from there.
        $arg = \explode('/', $path);
        if ('node' === $arg[0]) {
            // Whitelist our custom node URLs.
            if (\is_numeric($arg[1])) {
                if (isset($arg[2])) {
                    switch ($arg[2]) {
                        case 'duplicate':
                        case 'clone':
                        case 'edit':
                        case 'gallery':
                        case 'seo-edit':
                            return true;
                    }
                }

                // All nodes display should always be allowed.
                return true;
            }
        }

        // Our proper logic is following.
        if ('system/ajax' === $path) {
            return true;
        }

        /*
         * @todo answer true for all ajax requests/urls
         */

        // Give a chance to contrib modules to override this.
        /*
         * @todo restore this
         *
        $ret = module_invoke_all('ucms_path_is_allowed', $path);
        if (\in_array(false, $ret, true)) {
            return false;
        } else if (\in_array(true, $ret, true)) {
            return true;
        }
         * /

        $pathinfo = $path;
        if ('/' !== \substr($path, 0, 1)) {
            // @todo this is so wrong, we need to build a valid path info
            //   using the base url, or find another way
            $pathinfo = '/'.$pathinfo;
        }

        $isAdmin = false;
        try {
            // @todo Drupal 8 does not handle circular dependencies very well yet
            $router = \Drupal::service('router');
            /** @var \Symfony\Component\Routing\Route $route * /
            $route = $router->match($pathinfo)['_route_object'];
            $isAdmin = $route->hasOption('_admin_route');
        } catch (\Exception $e) {
            // Last resort options.
            $isAdmin = 'admin' === \substr($path, 0, 5);
        }

        if ($isAdmin) {
            // Allow node/add
            if ('node/add' === \substr($path, 0, 8)) {
                return true;
            }
            if ('admin/dashboard/tree' === \substr($path, 0, 20)) {
                return true;
            }
            if ('admin/dashboard/content' === \substr($path, 0, 23)) {
                return true;
            }
            if ('admin/dashboard/media' === \substr($path, 0, 21)) {
                return true;
            }

            return false;
        }

        return true;
    }
     */

    /**
     * Get URL in site
     *
     * @param int|Site $site
     *   Site identifier, if site is null
     * @param string $path
     *   Drupal path to hit in site
     * @param mixed[] $options
     *   Link options, see url()
     * @param boolean $dropDestination
     *   If you're sure you are NOT in a form, just set this to true
     *
     * @return mixed
     *   First value is the string path
     *   Second value are updates $options
     *
    public function getRouteAndParams($site, $path = null, array $options = [], bool $ignoreSso = false, bool $dropDestination = false): array
    {
        if (!$site instanceof Site) {
            $site = $this->storage->findOne($site);
        }

        // Avoid reentrancy in ucms_site_url_outbound_alter().
        $options['ucms_processed'] = true;
        $options['ucms_site'] = $site->getId();

        if (!$path) {
            $path = '<front>';
        }

        // Site is the same, URL should not be absolute; or if it asked that it
        // might be, then let Drupal work his own base URL, since it's the right
        // site already, ignore 'https' directive too because we cannot give the
        // user pages with insecure mixed mode links within.
        if ($this->manager->hasContext() && $this->manager->getContext()->getId() == $site->getId()) {
            return [$path, $options];
        }

        // @todo Should bypass this if user is not logged in
        // Reaching here means that we do asked for an absolute URL.
        if ($ignoreSso || !$this->ssoEnabled) {
            $this->forceSiteUrl($options, $site);

            return [$path, $options];
        }

        if (!$dropDestination) {
            if (isset($_GET['destination'])) {
                $options['query']['form_redirect'] = $_GET['destination'];
                unset($_GET['destination']);
            } else if (isset($options['query']['destination'])) {
                $options['query']['form_redirect'] = $options['query']['destination'];
            }
        }

        // Strip path when front page, avoid a potentially useless destination
        // parameter and normalize all different front possible paths.
        if ($path && '<front>' !== $path && $path !== variable_get('site_frontpage', 'node')) {
            $options['query']['destination'] = $path;
        }

        return ['sso/goto/' . $site->getId(), $options];
    }
     */

    /**
     * Alias of getRouteAndParams() that returns the generated URL
     *
     * @param int|Site $site
     *   Site identifier, if site is null
     * @param string $path
     *   Drupal path to hit in site
     * @param mixed[] $options
     *   Link options, see url()
     * @param boolean $dropDestination
     *   If you're sure you are NOT in a form, just set this to true
     *
     * @return string
     *
    public function generateUrl($site, $path = null, array $options = [], bool $ignoreSso = false, bool $dropDestination = false): string
    {
        return call_user_func_array([$this, 'url'], $this->getRouteAndParams($site, $path, $options, $ignoreSso, $dropDestination));
    }
     */
}
