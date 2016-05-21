<?php

namespace MakinaCorpus\Ucms\Drop\Notification\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Registers droppable instances, using the 'ucms_drop.droppable' tag
 */
class RegisterDroppablesPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('ucms_drop.registry') && !$container->hasAlias('ucms_drop.registry')) {
            return;
        }
        $definition = $container->findDefinition('ucms_drop.registry');

        foreach ($container->findTaggedServiceIds('ucms_drop.droppable') as $id => $attributes) {

            $def = $container->getDefinition($id);

            if (!$def->isPublic()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must be public as droppables are lazy-loaded.', $id));
            }
            if ($def->isAbstract()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must not be abstract as droppables are lazy-loaded.', $id));
            }

            // We must assume that the class value has been correctly filled, even if the service is created by a factory
            //   - note from myself: this is documented that it should alway be
            //     in the official dependency injection documentation
            $class = $container->getParameterBag()->resolveValue($def->getClass());

            $refClass = new \ReflectionClass($class);
            $interface = '\MakinaCorpus\Ucms\Drop\DropHandlerInterface';
            if (!$refClass->implementsInterface($interface)) {
                throw new \InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, $interface));
            }

            $definition->addMethodCall('registerService', [$id]);
        }
    }
}
