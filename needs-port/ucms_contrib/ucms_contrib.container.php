<?php

namespace Drupal\Module\ucms_contrib;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;

use MakinaCorpus\Ucms\Contrib\DependencyInjection\Compiler\EntityLinkFilterRegisterPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class ServiceProvider
 * @package Drupal\Module\ucms_dashboard
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerBuilder $container)
    {
        $container->addCompilerPass(new EntityLinkFilterRegisterPass());
    }
}
