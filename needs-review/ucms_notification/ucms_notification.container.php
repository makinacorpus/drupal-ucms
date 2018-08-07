<?php

namespace Drupal\Module\ucms_notification;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;

use MakinaCorpus\Ucms\Notification\DependencyInjection\Compiler\NotificationCompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class ServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerBuilder $container)
    {
        $container->addCompilerPass(new NotificationCompilerPass());
    }
}
