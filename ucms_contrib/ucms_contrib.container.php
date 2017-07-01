<?php

namespace Drupal\Module\ucms_contrib;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use MakinaCorpus\Ucms\Contrib\DependencyInjection\Compiler\EntityLinkFilterRegisterPass;
use MakinaCorpus\Ucms\Contrib\DependencyInjection\ContribBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Drupal 8 service provider implementation
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerBuilder $container)
    {
        $container->addCompilerPass(new EntityLinkFilterRegisterPass());

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/config'));
        $loader->load('pages.yml');
    }

    /**
     * {@inhertidoc}
     */
    public function registerBundles()
    {
        return [
            new ContribBundle(),
        ];
    }
}
