<?php

namespace MakinaCorpus\Ucms\Site\Environment;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SiteConfigOverrides implements ConfigFactoryOverrideInterface
{
    private $container;

    /**
     * Default constructor
     *
     * IMPORTANT: Injecting the container instead of the site manager instance
     * directly is mandatory here, because Drupal doesn't handle service circular
     * dependencies very well.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function loadOverrides($names)
    {
        $ret = [];

        /** @var \MakinaCorpus\Ucms\Site\SiteManager $manager */
        $manager = $this->container->get('ucms_site.manager');

        if (!$manager->hasContext()) {
            return $ret;
        }

        if (in_array('system.site', $names)) {
            $site = $manager->getContext();

            $ret['system.site']['name'] = $site->getTitle();

            if ($site->hasHome()) {
                $ret['system.site']['page']['front'] = '/node/'.$site->getHomeNodeId();
            }
        }

        return $ret;
    }

    /**
     * The string to append to the configuration static cache name.
     *
     * @return string
     *   A string to append to the configuration static cache name.
     */
    public function getCacheSuffix()
    {
        return SiteConfigOverrides::class;
    }

    /**
     * {@inheritdoc}
     */
    public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheableMetadata($name)
    {
        $cache = new CacheableMetadata();

        if ('system.site' === $name) {
            $cache->addCacheContexts(['site']);
        }

        return $cache;
    }
}
