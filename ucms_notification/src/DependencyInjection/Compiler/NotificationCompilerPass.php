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
            $definitions = [
                'ucms_notification.content_add',
                'ucms_notification.content_edit',
                'ucms_notification.content_delete',
            ];
            foreach ($definitions as $definition) {
                $formatter = $container->getDefinition($definition);

                $formatter->addMethodCall(
                    'setTypeHandler',
                    [new Reference('ucms_contrib.type_handler')]
                );
            }
        }
    }
}
