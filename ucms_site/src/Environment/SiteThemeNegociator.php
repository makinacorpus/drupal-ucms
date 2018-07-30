<?php

namespace MakinaCorpus\Ucms\Site\Environment;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use MakinaCorpus\Ucms\Site\SiteManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SiteThemeNegociator implements ThemeNegotiatorInterface
{
    private $configFactory;
    private $container;
    private $siteManager;

    /**
     * Default constructor
     *
     * IMPORTANT: Injecting the container instead of the site manager instance
     * directly is mandatory here, because Drupal doesn't handle service circular
     * dependencies very well.
     */
    public function __construct(ContainerInterface $container, ConfigFactoryInterface $configFactory)
    {
        $this->container = $container;
        $this->configFactory = $configFactory;
    }

    /**
     * Get site manager
     */
    private function getSiteManager(): SiteManager
    {
        if (!$this->siteManager) {
            $this->siteManager = $this->container->get('ucms_site.manager');
        }
        return $this->siteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function applies(RouteMatchInterface $route_match)
    {
        return true;
    }

    /**
     * Determine the active theme for the request.
     *
     * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
     *   The current route match object.
     *
     * @return string|null
     *   The name of the theme, or NULL if other negotiators, like the configured
     *   default one, should be used instead.
     */
    public function determineActiveTheme(RouteMatchInterface $route_match)
    {
        $manager = $this->getSiteManager();

        if ($manager->isMaster()) {
            $adminTheme = $this->configFactory->get('system.theme')->get('admin');
            if (!$adminTheme) {
                $adminTheme = 'seven';
            }
            return $adminTheme;
        }

        if ($manager->hasContext()) {
            return $manager->getContext()->getTheme() ?? 'bartik';
        }

        return null;
    }
}
