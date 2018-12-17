<?php

namespace MakinaCorpus\Ucms\Seo\Action;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Seo\Path\Redirect;
use MakinaCorpus\Ucms\Site\SiteManager;

/**
 * Provide a few links over the redirect items.
 */
class RedirectActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;

    private $siteManager;

    /**
     * Default constructor
     */
    public function __construct(SiteManager $siteManager)
    {
        $this->siteManager = $siteManager;
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item)
    {
        $ret = [];

        /** @var \MakinaCorpus\Ucms\Seo\Path\Redirect $item */
        $siteId = $item->getSiteId();

        $uri = $this->siteManager->getUrlGenerator()->generateUrl($siteId, 'node/'.$item->getNodeId());
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
