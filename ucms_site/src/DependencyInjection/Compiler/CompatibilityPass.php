<?php

namespace MakinaCorpus\Ucms\Site\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Configure other existing modules.
 */
class CompatibilityPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        // Remove some calista stuff from the container
        if ($container->hasDefinition('calista.action_provider.node')) {
            $container->removeDefinition('calista.action_provider.node');
        }
    }
}
