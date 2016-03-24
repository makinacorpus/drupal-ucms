<?php

namespace MakinaCorpus\Ucms\Tree\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TreeCompilerPass implements CompilerPassInterface
{

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('umenu.storage') || $container->hasAlias('umenu.storage')) {
            $eventListener = $container->getDefinition('ucms_tree.site_event_listener');

            $eventListener->addMethodCall(
                'setMenuStorage',
                [new Reference('umenu.storage')]
            );
        }
    }
}
