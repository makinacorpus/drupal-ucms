<?php

namespace MakinaCorpus\Ucms\Contrib\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use MakinaCorpus\Ucms\Contrib\Page\AdminNodePageType;
use MakinaCorpus\Ucms\Contrib\Controller\NodeAdminController;

class RegisterAdminPageTypesPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        // @todo
        //   - ensure it runs BEFORE it gets registered by widget factory
        //   - make the tabs and pools dynamic (see type handler)
        foreach (['mine', 'local', 'global', 'flagged', 'starred'] as $pool) {
            foreach (['content', 'media'] as $tab) {
                $definition = new Definition();
                $definition->setClass(AdminNodePageType::class);
                $definition->setArguments([
                    new Reference('ucms_contrib.datasource.elastic'),
                    new Reference('ucms_contrib.type_handler'),
                    new Reference('ucms_site.manager'),
                    $tab,
                    NodeAdminController::getQueryFilter($pool)
                ]);
                $definition->setPublic(true);
                $definition->setShared(false);
                $container->addDefinitions([NodeAdminController::getServiceName($tab, $pool) => $definition]);
            }
        }
    }
}
