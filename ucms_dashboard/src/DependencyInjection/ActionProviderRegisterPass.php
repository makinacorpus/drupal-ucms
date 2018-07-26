<?php

namespace MakinaCorpus\Ucms\Dashboard\DependencyInjection;

use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * @codeCoverageIgnore
 */
class ActionProviderRegisterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('ucms_dashboard.action_provider_registry')) {
            return;
        }
        $definition = $container->getDefinition('ucms_dashboard.action_provider_registry');

        // Register custom action providers
        $taggedServices = $container->findTaggedServiceIds('ucms.action_provider');
        foreach ($taggedServices as $id => $attributes) {
            $def = $container->getDefinition($id);

            $class = $container->getParameterBag()->resolveValue($def->getClass());
            $refClass = new \ReflectionClass($class);

            if (!$refClass->implementsInterface(ActionProviderInterface::class)) {
                throw new \InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, ActionProviderInterface::class));
            }

            /*
            if ($container->has('security.authorization_checker') && $refClass->isSubclassOf(AbstractActionProvider::class)) {
                $def->addMethodCall('setAuthorizationChecker', [new Reference('security.authorization_checker')]);
            }
             */

            $definition->addMethodCall('register', [new Reference($id)]);
        }

        /*
         * FIXME
         *
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
            $parentClass = '\MakinaCorpus\Ucms\Dashboard\Action\AbstractActionProcessor';

            if (!$refClass->isSubclassOf($parentClass)) {
                throw new \InvalidArgumentException(sprintf('Service "%s" must implement extend "%s".', $id, $parentClass));
            }

            $definition->addMethodCall('register', [new Reference($id)]);
        }
         */
    }
}
