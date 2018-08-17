<?php

namespace MakinaCorpus\Ucms\Site\Environment;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use MakinaCorpus\Ucms\Site\SiteManager;

class SiteCacheContext implements CacheContextInterface
{
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
    public static function getLabel()
    {
        return new TranslatableMarkup("Site");
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        if ($this->siteManager->hasContext()) {
            return $this->siteManager->getContext()->getId();
        }
        return 'master';
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheableMetadata()
    {
        $cache = new CacheableMetadata();

        if ($this->siteManager->hasContext()) {
            $cache->addCacheTags(['node:'.$this->siteManager->getContext()->getId()]);
        } else {
            $cache->addCacheTags(['node:master']);
        }

        return $cache;
    }
}
