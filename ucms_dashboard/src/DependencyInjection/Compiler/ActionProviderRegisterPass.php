<?php

namespace MakinaCorpus\Ucms\Dashboard\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ActionProviderRegisterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('ucms_dashboard.action_provider_registry')) {
            return;
        }
        $definition = $container->getDefinition('ucms_dashboard.action_provider_registry');

        // Register custom action providers
        $taggedServices = $container->findTaggedServiceIds('ucms_dashboard.action_provider');
        foreach ($taggedServices as $id => $attributes) {
            $def = $container->getDefinition($id);

            $class = $container->getParameterBag()->resolveValue($def->getClass());
            $refClass = new \ReflectionClass($class);
            $interface = '\MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface';

            if (!$refClass->implementsInterface($interface)) {
                throw new \InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, $interface));
            }

            $definition->addMethodCall('register', [new Reference($id)]);
        }

        if (!$container->hasDefinition('ucms_dashboard.processor_registry')) {
            return;
        }
        $definition = $container->getDefinition('ucms_dashboard.processor_registry');

        // Register automatic action provider based on action processors
        $taggedServices = $container->findTaggedServiceIds('ucms_dashboard.action');
        foreach ($taggedServices as $id => $attributes) {
            $def = $container->getDefinition($id);

            $class = $container->getParameterBag()->resolveValue($def->getClass());
            $refClass = new \ReflectionClass($class);
            $interface = '\MakinaCorpus\Ucms\Dashboard\Action\ActionProcessorInterface';

            if (!$refClass->implementsInterface($interface)) {
                throw new \InvalidArgumentException(sprintf('Service "%s" must extend "%s".', $id, $interface));
            }

            $definition->addMethodCall('register', [new Reference($id)]);
        }
    }
}
