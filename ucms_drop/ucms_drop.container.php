<?php

namespace Drupal\Module\ucms_drop;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;

use MakinaCorpus\Ucms\Drop\Notification\DependencyInjection\Compiler\RegisterDroppablesPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class ServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerBuilder $container)
    {
        $container->addCompilerPass(new RegisterDroppablesPass());
    }
}
