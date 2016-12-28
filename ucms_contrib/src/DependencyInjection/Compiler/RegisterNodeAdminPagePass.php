<?php

namespace MakinaCorpus\Ucms\Contrib\DependencyInjection\Compiler;

use MakinaCorpus\Ucms\Contrib\ContentTypeManager;
use MakinaCorpus\Ucms\Contrib\Page\DefaultNodeAdminPage;
use MakinaCorpus\Ucms\Contrib\Page\NodeAdminPageInterface;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class RegisterNodeAdminPagePass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasParameter('ucms_contrib_admin_pages')) {
            return;
        }

        if ($container->hasParameter('ucms_contrib_admin_tabs')) {
            $tabs = $container->getParameter('ucms_contrib_admin_tabs');
        } else {
            // Set a default value, site cannot work without this
            $tabs = ['content' => "Content", 'media' => "Media"];
            $container->setParameter('ucms_contrib_admin_tabs', $tabs);
        }

        $pages = $container->getParameter('ucms_contrib_admin_pages');
        $adminPages = [];
        $definitions = [];

        foreach (array_keys($tabs) as $tab) {
            foreach ($pages as $path => $pageDefinition) {

                if (empty($pageDefinition['name'])) {
                    throw new \InvalidArgumentException(sprintf('page "%s" definition set contain the "name" value.', $path));
                }

                // Allow developers to set their own service from scratch
                if (isset($pageDefinition['service'])) {
                    $definition = $container->getDefinition($pageDefinition['service']);

                    // Ensure service has the right class
                    $class = $container->getParameterBag()->resolveValue($definition->getClass());
                    $refClass = new \ReflectionClass($class);
                    $interface = NodeAdminPageInterface::class;

                    if (!$refClass->implementsInterface($interface)) {
                        throw new \InvalidArgumentException(sprintf('service "%s" must implement interface "%s".', $pageDefinition['service'], $interface));
                    }
                } else {
                    if (empty($pageDefinition['permission'])) {
                        throw new \InvalidArgumentException(sprintf('page "%s" definition set contain the "permission" value.', $path));
                    }

                    $definition = new Definition();
                    $definition->setClass(DefaultNodeAdminPage::class);
                    $definition->setArguments([
                        new Reference('ucms_contrib.datasource.elastic'),
                        new Reference('ucms_site.manager'),
                        new Reference('ucms_contrib.type_manager'),
                        $pageDefinition['permission'],
                        $tab,
                        $pageDefinition['filter_query'],
                    ]);
                }

                // The definitions must not be shared, datasource is not shared
                // either and we cannot allow state to interfer because in some
                // site pages, we might have the same datasource used more than
                // once.
                $definition->setShared(false);

                // They need to be public because the admin widget factory will
                // build them dynamically on-demand
                $definition->setPublic(true);

                // Pages are both node admin pages and a more generic page builder
                // type, which needs to be registered to the admin widget factory,
                // we are going to set the right tab onto it.
                $definition->addTag('ucms_dashboard.page_type');

                $adminPages[$path] = $pageDefinition['name'];
                $definitions[ContentTypeManager::getServiceName($tab, $path)] = $definition;
            }
        }

        if ($container->has('ucms_contrib.type_manager')) {
            $container
                ->getDefinition('ucms_contrib.type_manager')
                ->setArguments([
                    new Reference('database'),
                    new Reference('event_dispatcher'),
                    $tabs,
                    $adminPages,
                ])
            ;
        }
        if ($definitions) {
            $container->addDefinitions($definitions);
        }
    }
}
