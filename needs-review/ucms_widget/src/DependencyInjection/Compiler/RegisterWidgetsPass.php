<?php

namespace MakinaCorpus\Ucms\Widget\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class RegisterWidgetsPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('ucms_widget.registry') && !$container->hasAlias('ucms_widget.registry')) {
            return;
        }
        $definition = $container->findDefinition('ucms_widget.registry');

        $map = [];

        foreach ($container->findTaggedServiceIds('ucms_widget') as $id => $attributes) {

            $def = $container->getDefinition($id);

            if (!$def->isPublic()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must be public as widgets are lazy-loaded.', $id));
            }
            if ($def->isAbstract()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must not be abstract must be public as widgets are lazy-loaded.', $id));
            }

            if (empty($attributes[0]['type'])) {
                throw new \InvalidArgumentException(sprintf("The service \"%s\" tags must carry the 'type' attribute for registry.", $id));
            }
            $type = $attributes[0]['type'];

            // We must assume that the class value has been correctly filled, even if the service is created by a factory
            //   - note from myself: this is documented that it should alway be
            //     in the official dependency injection documentation
            $class = $container->getParameterBag()->resolveValue($def->getClass());

            $refClass = new \ReflectionClass($class);
            $interface = '\MakinaCorpus\Ucms\Widget\WidgetInterface';
            if (!$refClass->implementsInterface($interface)) {
                throw new \InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, $interface));
            }

            $map[$type] = $id;
        }

        if ($map) {
            $definition->addMethodCall('registerAll', [$map]);
        }
    }
}
