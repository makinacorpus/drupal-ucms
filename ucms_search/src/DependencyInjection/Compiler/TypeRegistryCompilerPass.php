<?php

namespace MakinaCorpus\Ucms\Search\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class TypeRegistryCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('ucms_search.mapping.type_registry')) {
            return;
        }

        $definition = $container->getDefinition('ucms_search.mapping.type_registry');

        $taggedServices = $container->findTaggedServiceIds('ucms_search.mapping.type');

        foreach ($taggedServices as $id => $attributes) {

            // FIXME I am not proud of this one but I can't manage to set
            // arbitrary attributes on my tagged services
            $typeDefinition = $container->getDefinition($id);
            $type = $typeDefinition->getArguments();
            $typeDefinition->setArguments([]);

            $definition->addMethodCall(
                'regisgter',
                [reset($type), new Reference($id)]
            );
        }
    }
}
