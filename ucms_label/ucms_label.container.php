<?php

namespace Drupal\Module\ucms_label;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;

use MakinaCorpus\Ucms\Label\DependencyInjection\Compiler\NotificationSupportCompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

class ServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerBuilder $container)
    {
        $container->addCompilerPass(new NotificationSupportCompilerPass(), PassConfig::TYPE_REMOVE);
    }
}
