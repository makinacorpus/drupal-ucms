<?php

namespace Drupal\Module\ucms_site;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use MakinaCorpus\Ucms\Site\DependencyInjection\Compiler\CompatibilityPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

class ServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/config'));
        $loader->load('pages.yml');

        $container->addCompilerPass(new CompatibilityPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 50 /* Run before calista */);
    }
}
