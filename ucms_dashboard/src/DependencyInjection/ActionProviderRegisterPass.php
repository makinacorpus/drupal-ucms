<?php

namespace MakinaCorpus\Ucms\Dashboard\DependencyInjection;

use MakinaCorpus\Ucms\Dashboard\Action\AbstractActionProvider;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Dashboard\Action\ItemLoaderInterface;
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

        $loaders = [];
        $providers = [];

        // Register custom action providers
        $taggedServices = $container->findTaggedServiceIds('ucms.action_provider');
        foreach ($taggedServices as $id => $attributes) {
            $def = $container->getDefinition($id);

            $class = $container->getParameterBag()->resolveValue($def->getClass());
            $refClass = new \ReflectionClass($class);

            if (!$refClass->implementsInterface(ActionProviderInterface::class)) {
                throw new \InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, ActionProviderInterface::class));
            }

            if ($container->has('security.authorization_checker') && $refClass->isSubclassOf(AbstractActionProvider::class)) {
                $def->addMethodCall('setAuthorizationChecker', [new Reference('security.authorization_checker')]);
            }

            $providers[] = new Reference($id);
        }

        // Register custom item loaders
        $taggedServices = $container->findTaggedServiceIds('ucms.item_loader');
        foreach ($taggedServices as $id => $attributes) {
            $def = $container->getDefinition($id);
            $class = $container->getParameterBag()->resolveValue($def->getClass());
            $refClass = new \ReflectionClass($class);
            if (!$refClass->implementsInterface(ItemLoaderInterface::class)) {
                throw new \InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, ItemLoaderInterface::class));
            }
            $loaders[] = new Reference($id);
        }

        $definition->setArguments([$providers, $loaders]);
    }
}
