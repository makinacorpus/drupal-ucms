<?php

namespace Drupal\Module\ucms_dashboard;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;

use MakinaCorpus\Ucms\Dashboard\DependencyInjection\Compiler\ActionProviderRegisterPass;
use MakinaCorpus\Ucms\Dashboard\DependencyInjection\Compiler\PortletRegisterPass;

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
        $container->addCompilerPass(new ActionProviderRegisterPass());
        $container->addCompilerPass(new PortletRegisterPass());
    }
}
