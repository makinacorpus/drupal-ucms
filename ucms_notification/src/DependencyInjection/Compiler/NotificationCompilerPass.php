<?php

namespace MakinaCorpus\Ucms\Notification\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class NotificationCompilerPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('ucms_contrib.type_handler')) {
            $taggedServices = $container->findTaggedServiceIds('ucms_contrib.type_handler');

            foreach ($taggedServices as $id => $attributes) {
                $formatter = $container->getDefinition($id);

                $formatter->addMethodCall(
                    'setTypeHandler',
                    [new Reference('ucms_contrib.type_handler')]
                );
            }
        }

        if ($container->hasDefinition('ucms_site.manager')) {
            $taggedServices = $container->findTaggedServiceIds('ucms_site.manager');

            foreach ($taggedServices as $id => $attributes) {
                $definition = $container->getDefinition($id);

                $definition->addMethodCall(
                    'setSiteManager',
                    [new Reference('ucms_site.manager')]
                );
            }
        }
    }
}
