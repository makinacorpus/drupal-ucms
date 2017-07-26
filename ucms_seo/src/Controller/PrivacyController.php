<?php

namespace MakinaCorpus\Ucms\Seo\Controller;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Site\Site;

/**
 * Privacy settings controller
 */
class PrivacyController extends Controller
{
    /**
     * This actions only serves the purpose of delivering a static template,
     * everything else will be managed using JavaScript: we cannot break page
     * cache because of client settings.
     */
    public function clientSettingsAction(Site $currentSite)
    {
        $providers = [];

        if ($currentSite->hasAttribute('seo.piwik.url')) {
            $providers['piwik'] = "Piwik";
        }
        if ($currentSite->hasAttribute('seo.google.ga_id')) {
            $providers['google-analytics'] = "Google Analytics";
        }

        return $this->render('module:ucms_seo:views/privacy-settings.html.twig', ['providers' => $providers]);
    }
}
