<?php

namespace Drupal\Module\ucms_dashboard;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;

use MakinaCorpus\Ucms\Dashboard\DependencyInjection\Compiler\ActionProviderRegisterPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class ServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ActionProviderRegisterPass());
    }
}
