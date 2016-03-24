<?php

namespace MakinaCorpus\Ucms\Seo\Twig;

use MakinaCorpus\Ucms\Seo\SeoService;
use MakinaCorpus\Ucms\Site\SiteManager;

class SeoExtension extends \Twig_Extension
{
    /**
     * @var SeoService
     */
    private $service;

    /**
     * @var SiteManager
     */
    private $siteManager;

    public function __construct(SeoService $service, SiteManager $siteManager)
    {
        $this->service = $service;
        $this->siteManager = $siteManager;
    }

    public function getName()
    {
        return 'ucms_seo';
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'google_analytics',
                [$this, 'renderGoogleAnalytics'],
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
        ];
    }

    public function renderGoogleAnalytics(\Twig_Environment $twig)
    {
        if ($this->siteManager->hasContext()) {
            $site = $this->siteManager->getContext();
            if ($site->hasAttribute('seo.google.ga_id')) {
                return $twig->render('module:ucms_seo:views/ga.html.twig',['googleAnalyticsId' => $site->getAttribute('seo.google.ga_id')]);
            }
        }
    }
}
