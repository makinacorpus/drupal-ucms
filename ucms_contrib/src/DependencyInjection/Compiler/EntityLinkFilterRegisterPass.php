<?php

namespace MakinaCorpus\Ucms\Contrib\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class EntityLinkFilterRegisterPass implements CompilerPassInterface
{

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (class_exists('MakinaCorpus\ULink\EventDispatcher\EntityLinkFilterEvent')) {
            $definition = new Definition('MakinaCorpus\Ucms\Contrib\EventDispatcher\EntityLinkFilterEventSubscriber');
            $definition->setArguments([new Reference("ucms_site.manager"), new Reference("ucms_site.node_manager")]);
            $definition->addTag('event_subscriber');
            $container->addDefinitions([$definition]);
        }
    }
}
