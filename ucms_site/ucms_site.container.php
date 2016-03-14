<?php

namespace Drupal\Module\ucms_site;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;

use MakinaCorpus\Ucms\Site\DependencyInjection\Compiler\SiteEventListenerCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerBuilder $container)
    {
        $container->addCompilerPass(new SiteEventListenerCompilerPass());
    }
}
