<?php

namespace MakinaCorpus\Ucms\Site\Environment;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\node\NodeInterface;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\Security\AuthTokenStorage;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Deals with platform wide inter-sites URL generation.
 *
 * @todo rework destination parameter handling correctly
 */
class CrossSiteUrlGenerator implements UrlGeneratorInterface
{
    use DependencySerializationTrait;

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

    private $authTokenStorage;
    private $nested;
    private $routeProvider;
    private $siteManager;
    private $ssoEnabled = true; // @todo make this configurable

    /**
     * Default constructor
     */
    public function __construct(SiteManager $siteManager, AuthTokenStorage $authTokenStorage, UrlGeneratorInterface $nested, RouteProviderInterface $routeProvider)
    {
        $this->authTokenStorage = $authTokenStorage;
        $this->nested = $nested;
        $this->routeProvider = $routeProvider;
        $this->siteManager = $siteManager;
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
     * Append site data to route
     */
    private function generateInSite(Site $site, $name, $parameters = [], $options = [], $collectBubbleableMetadata = false)
    {
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

        $options = $this->appendSsoData($site->getId(), $options);

        return $this->nested->generateFromRoute($name, $parameters, $options, $collectBubbleableMetadata);
    }

    /**
     * Append SSO parameters
     */
    private function appendSsoData(int $siteId, array $options): array
    {
        if ($this->ssoEnabled && ($options['ucms_sso'] ?? false)) {
            // @todo fix me
            if ($userId = \Drupal::currentUser()->id()) {
                $authToken = $this->authTokenStorage->create($siteId, $userId);
                $options[CrossSiteAuthProvider::TOKEN_PARAMETER] = $authToken->getToken();
            }
        }

        return $options;
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
           return $this->generateInSite($site, $name, $parameters, $options, $collectBubbleableMetadata);

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
                // When in entity type configuration context, entity is not a
                // node but an node entity bundle instead, we have to ensure
                // that route element is a node.
                if (($node = $options['entity'] ?? null) instanceof NodeInterface) {
                    /** @var \Drupal\node\NodeInterface $node */
                    if ($siteId = $this->siteManager->findMostRelevantSiteFor($node)) {
                        // @todo should we catch exception and be resilient to errors here.?
                        if ($site = $manager->getStorage()->findOne($siteId)) {
                            return $this->generateInSite($site, $name, $parameters, $options, $collectBubbleableMetadata);
                        }
                    }
                }
            }
        }

        return $this->nested->generateFromRoute($name, $parameters, $options, $collectBubbleableMetadata);
    }
}
