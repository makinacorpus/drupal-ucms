<?php

namespace Drupal\ucms_dashboard;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use MakinaCorpus\Calista\Twig\DependencyInjection\RegisterNamespaceCompilerPass;
use MakinaCorpus\Calista\View\DependencyInjection\RendererRegisterCompilerPass;
use MakinaCorpus\Ucms\Dashboard\DependencyInjection\ActionProviderRegisterPass;

class UcmsDashboardServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ActionProviderRegisterPass());
        $container->addCompilerPass(new RendererRegisterCompilerPass());
        $container->addCompilerPass(new RegisterNamespaceCompilerPass('twig.loader.filesystem'));
    }
}
