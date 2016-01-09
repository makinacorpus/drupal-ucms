<?php

namespace Drupal\Module\ucms_search;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;

use MakinaCorpus\Ucms\Search\DependencyInjection\Compiler\TypeRegistryCompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class ServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TypeRegistryCompilerPass());
    }
}
