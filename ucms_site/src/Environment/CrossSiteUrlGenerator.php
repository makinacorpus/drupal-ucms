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
    const ALLOWED_SITE_ADMIN_ROUTES = [
        // Node screens, all node screens.
        'node.multiple_delete_confirm' => true,
        'entity.node.delete_multiple_form' => true,
        'entity.node.delete_form' => true,
        'entity.node.edit_form' => true,
        'node.add_page' => true,
        'node.add' => true,
        'entity.node.view' => true,
        'entity.node.preview' => true,
        'entity.node.version_history' => true,
        'entity.node.revision' => true,
        'node.revision_revert_confirm' => true,
        'node.revision_revert_translation_confirm' => true,
        'node.revision_delete_confirm' => true,

        //
        // Arbitrary ones below.
        //
        // @todo use paths, instead of this.
        //
        //   To be noted that Drupal with its menu alteration allows views and
        //   modules to override paths, but the route system allows paths to
        //   be dynamic and be changed, ideally, that's the whole point of it.
        //   So, in the end, which is the canonical one ? The route name or the
        //   Drupal path ?
        //
        //   There is nothing canonical, in the end.
        //
        //   I guess that in Drupal developers own head, the canonical one is
        //   probably still the path, which in the end, in my opinion, defies
        //   the whole point of using routes everywhere.
        //
        //   For exemple, if I set a redirect on a form to the content admin
        //   list, which is the route, should I guess it is a view ? A module
        //   page using some hook alter ? Or is it the core default when views
        //   module is not enabled ?
        //
        //   Bah. Stupid. I hate Drupal - so many inconsistencies.
        //
        // Anyway, we need the paths for this check, we cannot use routes.
        //

        'view.content.page_1' => true, // /admin/content
       'system.admin_content' => true, // /admin/content (Yes, there are two of it!)
       'view.files.page_1' => true, // /admin/content/files
       'view.files.page_2' => true, // /admin/content/files/usage/{arg_0}
       'entity.media.collection' => true, // /admin/content/media
       'view.media.media_page_list' => true, // /admin/content/media (Two, once again)
       'entity.media.multiple_delete_confirm' => true, // /admin/content/media/delete
       'entity.node.delete_multiple_form' => true, // /admin/content/node/delete
       'node.multiple_delete_confirm' => true, // /admin/content/node/delete
    ];

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
     *
     * @todo answer true for all ajax requests/urls
     */
    private function isRouteAllowedOnSite(string $route): bool
    {
        if (!$this->routeProvider) {
            return true;
        }

        if (isset(self::ALLOWED_SITE_ADMIN_ROUTES[$route])) {
            return true;
        }

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
         */

        return !$this->routeProvider->getRouteByName($route)->hasOption('_admin_route');
    }

    /**
     * Is route allowed on master
     *
     * @todo answer true for all ajax requests/urls
     */
    private function isRouteAllowedInMaster(string $route): bool
    {
        if (!$this->routeProvider) {
            return true;
        }

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
         */

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
            // Check for route node, and redirect to suitable site.
            // @todo this is ugly, rewrite this
            if ('entity.node' === substr($name, 0, 11)) {
                /** @var \Drupal\node\NodeInterface $node */
                if ($node = $options['entity'] ?? null) {
                    if ($siteId = $this->siteManager->findMostRelevantSiteFor($node)) {
                        // @todo should we catch exception and be resilient to errors here.?
                        if ($site = $manager->getStorage()->findOne($siteId)) {

                            if (Site::ALLOWED_PROTOCOL_PASS !== $site->getAllowedProtocols()) {
                                if ($options['https'] = $site->isHttpsAllowed()) {
                                    $options['base_url'] = 'https://'.$site->getHostname();
                                } else {
                                    $options['base_url'] = 'http://'.$site->getHostname();
                                }
                            } else {
                                // @todo find http/https from request isSecure() instead.
                                //    This probably will need to inject the requeststack service.
                                $options['base_url'] = ($options['https'] ?? false ? 'https' : 'http').'://'.$site->getHostname();
                            }

                            return $this->nested->generateFromRoute($name, $parameters, $options, $collectBubbleableMetadata);
                        }
                    }
                }
            }
        }

        return $this->nested->generateFromRoute($name, $parameters, $options, $collectBubbleableMetadata);
    }

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
}
