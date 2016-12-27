<?php

namespace MakinaCorpus\Ucms\Dashboard\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;

class PageBuilderRegisterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('ucms_dashboard.admin_widget_factory')) {
            return;
        }
        $definition = $container->getDefinition('ucms_dashboard.admin_widget_factory');

        $types = [];

        // Register custom action providers
        $taggedServices = $container->findTaggedServiceIds('ucms_dashboard.page_type');
        foreach ($taggedServices as $id => $attributes) {
            $def = $container->getDefinition($id);

            $class = $container->getParameterBag()->resolveValue($def->getClass());
            $refClass = new \ReflectionClass($class);
            $interface = '\MakinaCorpus\Ucms\Dashboard\Page\PageTypeInterface';

            if (!$refClass->implementsInterface($interface)) {
                throw new \InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, $interface));
            }

            if (empty($attributes[0]['id'])) {
                $typeId = $def->getClass();
            } else {
                $typeId = $attributes[0]['id'];
            }

            $types[$typeId] = $id;
        }

        if ($types) {
            $definition->addMethodCall('registerPageTypes', [$types]);
        }
    }
}
