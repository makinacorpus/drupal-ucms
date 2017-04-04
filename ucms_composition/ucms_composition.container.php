<?php

namespace Drupal\Module\ucms_composition;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use MakinaCorpus\Drupal\Layout\DependencyInjection\Compiler\DisableDefaultLayoutCollectorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Bends the phplayout driven services.
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerBuilder $container)
    {
        $container->addCompilerPass(new DisableDefaultLayoutCollectorPass());
    }
}
