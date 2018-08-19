<?php

namespace MakinaCorpus\Ucms\Site\DependencyInjection;

use MakinaCorpus\Ucms\Site\Environment\SiteRouteProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Drupal\Core\Routing\RouteProvider;

/**
 * @codeCoverageIgnore
 */
class DrupalOverridesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('router.route_provider')) {
            $definition = $container->getDefinition('router.route_provider');
            if (RouteProvider::class !== $definition->getClass()) {
                throw new \Exception(\sprintf(
                    "Another module overrides the '%s' service with should have class '%s' but now has class '%s'",
                    'router.route_provider', RouteProvider::class, $definition->getClass()
                ));
            }
            $definition->setClass(SiteRouteProvider::class);
        }
    }
}
