<?php

namespace MakinaCorpus\Ucms\Dashboard\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class PortletRegisterPass
 * @package MakinaCorpus\Ucms\Dashboard\DependencyInjection\Compiler
 */
class PortletRegisterPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('ucms_dashboard.portlet_registry')) {
            return;
        }

        $definition = $container->getDefinition('ucms_dashboard.portlet_registry');

        $taggedServices = $container->findTaggedServiceIds('ucms_dashboard.portlet');


        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('addPortlet', [new Reference($id), $id]);
        }
    }
}
