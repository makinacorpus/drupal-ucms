<?php

namespace MakinaCorpus\Ucms\Site\Environment;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use MakinaCorpus\Ucms\Site\SiteManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes the inbound path using path alias lookups.
 */
class CrossSitePathProcessor implements OutboundPathProcessorInterface
{
    private $entityTypeManager;
    private $siteManager;

    /**
     * Default constructor
     */
    public function __construct(EntityTypeManager $entityTypeManager, SiteManager $siteManager)
    {
        $this->entityTypeManager = $entityTypeManager;
        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function processOutbound($path, &$options = [], Request $request = null, BubbleableMetadata $bubbleableMetadata = null)
    {
        if ($options['external'] ?? false) {
            return;
        }

        // Avoid reentrancy, especially if the URL was generated using the site
        // manager, case in which we don't need to process it.
        if (isset($options['ucms_processed'])) {
            return;
        }

        $options['ucms_processed'] = true;
        $options['ucms_site'] = false;

        $manager = $this->siteManager;
        $urlBuilder = $manager->getUrlGenerator();

        if ($manager->hasContext()) {
            // Enforce master-only (administration pages) links to be absolute with the
            // master hostname instead of being directed to the current site.
            if ($path && !$urlBuilder->isPathAllowedOnSite($path)) {
                $options['absolute'] = true;
                $options['external'] = true;
                if ($request) {
                    $baseUrl = ($request->isSecure() ? 'https' : 'http').';//'.$manager->getMasterHostname().'/';
                } else {
                    $baseUrl = 'http://'.$manager->getMasterHostname().'/';
                }
                $path = $baseUrl.$path;
            } else {
                $options['ucms_site'] = $manager->getContext()->getId();
            }
        } else {
            if ($request && !$request->isMethod('get')) {
                return;
            }

            // In case we have no site context, and we are actually trying to render a
            // node link, we must enforce a local site to display it when relevant,
            // especially that most nodes can't be seen on master.
            $matches = [];

            if (\preg_match('#^node/(\d+)(|/view)$#', $path, $matches)) {
                // Please note that the following algorith will probably be very heavy
                // in term of performances, but it can only happens in the administration
                // pages, in which nodes will almost always be preloaded one prior to
                // generating links to it.
                if ($node = $this->entityTypeManager->getStorage('node')->load($matches[1])) {
                    /** @var \MakinaCorpus\Ucms\Site\NodeAccessService $nodeAccessService */
                    $nodeAccessService = \Drupal::service('ucms_site.node_access_helper');
                    // findMostRelevantSiteFor() allows fetching URL for non enabled sites, but
                    // will always find a site the current user can see. This function will
                    // always be called when attempting to connect on the admin sites, so this
                    // fine to redirect on non enabled sites if no enabled site was found.
                    $mostRelevantSite = $nodeAccessService->findMostRelevantSiteFor($node);
                    if ($mostRelevantSite) {
                        $urlBuilder->forceSiteUrl($options, $mostRelevantSite);
                        $options['ucms_site'] = $mostRelevantSite;
                    }
                }
            }
        }
    }
}
