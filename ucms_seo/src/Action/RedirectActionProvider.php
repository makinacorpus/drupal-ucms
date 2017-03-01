<?php

namespace MakinaCorpus\Ucms\Seo\Action;

use MakinaCorpus\Drupal\Dashboard\Action\AbstractActionProvider;
use MakinaCorpus\Drupal\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Seo\Path\Redirect;
use MakinaCorpus\Ucms\Site\SiteManager;

/**
 * Provide a few links over the redirect items.
 */
class RedirectActionProvider extends AbstractActionProvider
{
    /**
     * @var SiteManager
     */
    private $siteManager;

    /**
     * Default constructor
     *
     * @param SiteManager $siteManager
     */
    public function __construct(SiteManager $siteManager)
    {
        $this->siteManager = $siteManager;
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item, $primaryOnly = false, array $groups = [])
    {
        $ret = [];

        /** @var \MakinaCorpus\Ucms\Seo\Path\Redirect $item */
        $siteId = $item->getSiteId();

        $uri = $this->siteManager->getUrlGenerator()->generateUrl($siteId, 'node/' . $item->getNodeId());
        $ret[] = new Action($this->t("Go to site"), $uri, null, 'share-alt');

        return $ret;
    }

    /**
     * {inheritdoc}
     */
    public function supports($item)
    {
        return $item instanceof Redirect;
    }
}
