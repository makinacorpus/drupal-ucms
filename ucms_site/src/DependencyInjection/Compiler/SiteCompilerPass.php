<?php

namespace MakinaCorpus\Ucms\Site\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SiteCompilerPass implements CompilerPassInterface
{

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('ucms_contrib.type_handler')) {
            $eventListener = $container->getDefinition('ucms_site.node_access_helper');

            $eventListener->addMethodCall(
                'setTypeHandler',
                [new Reference('ucms_contrib.type_handler')]
            );
        }
    }
}