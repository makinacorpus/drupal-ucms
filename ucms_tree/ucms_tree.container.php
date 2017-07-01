<?php

namespace Drupal\Module\ucms_tree;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use MakinaCorpus\Ucms\Tree\DependencyInjection\Compiler\TreeCompilerPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/config'));
        $loader->load('pages.yml');

        $container->addCompilerPass(new TreeCompilerPass());
    }
}
