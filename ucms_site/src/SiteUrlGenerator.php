<?php

namespace MakinaCorpus\Ucms\Site;

/**
 * Deals with platform wide inter-sites URL generation.
 */
class SiteUrlGenerator
{
    private $manager;
    private $storage;
    private $ssoEnabled = true;

    /**
     * Default constructor
     *
     * @param SiteManager $manager
     */
    public function __construct(SiteManager $manager)
    {
        $this->manager = $manager;
        $this->storage = $manager->getStorage();

        // @todo sad hack, we cannot use module_exists() because this will be
        //   instanciated during hook_boot() and modules are not yet been
        //   activated
        // $this->ssoEnabled = module_exists('ucms_sso');
    }

    /**
     * Alter parameters for the url outbound alter hook
     *
     * @param mixed[] $options
     *   Link options, see url()
     * @param int|Site $site
     *   Site identifier, if site is null
     */
    public function forceSiteUrl(&$options, $site)
    {
        if (!$site instanceof Site) {
            $site = $this->storage->findOne($site);
        }

        if ($GLOBALS['is_https']) {
            $options['https'] = !$site->isHttpsAllowed();
        } else if (variable_get('https', false)) {
            $options['https'] = !$site->isHttpAllowed();
        } else {
            // Sorry, HTTPS is disabled at the Drupal level, note that it is not
            // mandatory to this because url() will do the check by itself, but
            // because there is in here one code path in which we are going to
            // manually set the base URL, we need to compute that by ourselves.
            $options['https'] = false;
        }

        // Warning, this is a INTERNAL url() function behavior, so we have
        // to reproduce a few other behaviours to go along with it, such as
        // manual http vs https handling. Please see the variable_get('https')
        // documentation above.
        $options['base_url'] = ($options['https'] ? 'https://' : 'http://') . $site->getHostname();
        $options['absolute'] = true;
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
     */
    public function getRouteAndParams($site, $path = null, $options = [], $ignoreSso = false, $dropDestination = false)
    {
        if (!$site instanceof Site) {
            $site = $this->storage->findOne($site);
        }

        // Avoid reentrancy in ucms_site_url_outbound_alter().
        $options['ucms_processed'] = true;
        $options['ucms_site'] = $site->getId();

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
            // Compute destination using all query parameters we may have to
            // ensure the will be encoded right in the 'destination' parameter,
            // thus propagating it until the drupal_goto() call in the ucms_sso
            // module.
            $options['query'] = ['destination' => $path.'?'.\http_build_query($options['query'] ?? [])];
        }

        return ['sso/goto/' . $site->getId(), $options];
    }

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
     */
    public function generateUrl($site, $path = null, $options = [], $ignoreSso = false, $dropDestination = false)
    {
        return call_user_func_array('url', $this->getRouteAndParams($site, $path, $options, $ignoreSso, $dropDestination));
    }
}
