<?php

namespace MakinaCorpus\Ucms\Dashboard\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class PageBuilderRegisterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('ucms_dashboard.admin_widget_factory')) {
            return;
        }
        $definition = $container->getDefinition('ucms_dashboard.admin_widget_factory');

        // Register custom action providers
        $taggedServices = $container->findTaggedServiceIds('ucms_dashboard.page_builder');
        foreach ($taggedServices as $id => $attributes) {
            $def = $container->getDefinition($id);

            $class = $container->getParameterBag()->resolveValue($def->getClass());
            $refClass = new \ReflectionClass($class);
            $interface = '\MakinaCorpus\Ucms\Dashboard\Page\PageBuilder';

            if (!$refClass->name === $interface) {
                throw new \InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, $interface));
            }

            if (empty($attributes[0]['id'])) {
                throw new \InvalidArgumentException(sprintf('Service "%s" with tag "ucms_dashboard.page_builder" must have the "id" tag attribute.', $id, $interface));
            }

            $definition->addMethodCall('registerPageBuilder', [$attributes[0]['id'], new Reference($id)]);
        }
    }
}
