<?php

namespace MakinaCorpus\Ucms\Site\Twig\Extension;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;

class SiteExtension extends \Twig_Extension
{
    use StringTranslationTrait;
    use UrlGeneratorTrait;

    private $siteManager;

    /**
     * Default constructor
     */
    public function __construct(SiteManager $siteManager)
    {
        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('ucms_site_state', [$this, 'renderState'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('ucms_site_role', [$this, 'renderRole'], ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('ucms_site_state', [$this, 'renderState'], ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('ucms_site_type', [$this, 'renderType'], ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('ucms_site_url', [$this, 'renderSiteUrl']),
        ];
    }

    /**
     * Render state label
     */
    public function renderState($state): string
    {
        return SiteState::getList()[$state] ?? $this->t("Unknown");
    }

    /**
     * Render site type
     */
    public function renderType($type): string
    {
        return $this->siteManager->getTypeName($type ?? '');
    }

    /**
     * Render role label
     */
    public function renderRole($role): string
    {
        // FIXME: make this dynamic
        switch ((int)$role) {

            case Access::ROLE_WEBMASTER:
                return $this->t("Webmaster");

            case Access::ROLE_CONTRIB:
                return $this->t("Contributor");
        }

        return $this->t("Unknown");
    }

    /**
     * Render site link
     *
     * @param int|\MakinaCorpus\Ucms\Site\Site $site
     *   Site identifier, if site is null
     * @param string $route
     *   Drupal route to hit in site
     * @param mixed[] $params
     *   Route parameters, see \Symfony\Component\Routing\Generator\UrlGeneratorInterface::generateUrl()
     * @param mixed[] $options
     *   Link options, see \Drupal\Core\Routing\UrlGeneratorInterface::generateUrl()
     *
     * @return string
     */
    public function renderSiteUrl($site, $route = null, array $params = [], array $options = [], $ignoreSso = false, $dropDestination = true): string
    {
        return $this->url($route ?? '<front>', [], ['ucms_site' => $site]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ucms_site';
    }
}
