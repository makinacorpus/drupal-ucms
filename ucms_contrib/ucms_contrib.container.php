<?php

namespace Drupal\Module\ucms_contrib;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;

use MakinaCorpus\Ucms\Contrib\DependencyInjection\Compiler\EntityLinkFilterRegisterPass;
use MakinaCorpus\Ucms\Contrib\DependencyInjection\Compiler\RegisterNodeAdminPagePass;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Drupal 8 service provider implementation
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerBuilder $container)
    {
        $container->addCompilerPass(new EntityLinkFilterRegisterPass());
        $container->addCompilerPass(new RegisterNodeAdminPagePass());
    }
}
