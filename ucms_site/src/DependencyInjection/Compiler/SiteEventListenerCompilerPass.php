<?php

namespace MakinaCorpus\Ucms\Site\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SiteEventListenerCompilerPass implements CompilerPassInterface
{

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('umenu.storage')) {
            return;
        }

        $eventListener = $container->getDefinition('ucms_site.site_event_listener');

        $eventListener->addMethodCall(
            'setMenuStorage',
            [new Reference('umenu.storage')]
        );
    }
}
