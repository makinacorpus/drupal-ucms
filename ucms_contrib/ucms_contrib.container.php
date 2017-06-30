<?php

namespace Drupal\Module\ucms_contrib;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use MakinaCorpus\Ucms\Contrib\DependencyInjection\Compiler\EntityLinkFilterRegisterPass;
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
        $loader->load('pages.cart.yml');
        $loader->load('pages.node.yml'); // @todo make this conditional (let the user override)

        // Load known types as container parameter
        $media      = variable_get('ucms_contrib_tab_media_type', []);
        $editorial  = variable_get('ucms_contrib_editorial_types', []);
        $component  = variable_get('ucms_contrib_component_types', []);

        // Content = editorial + component
        $container->setParameter('ucms_contrib.type.content', array_merge($editorial, $component));
        // Editorial = media + content
        $container->setParameter('ucms_contrib.type.media', $media);
    }
}
