<?php

namespace Drupal\Module\ucms_tree;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;

use MakinaCorpus\Ucms\Tree\DependencyInjection\Compiler\TreeCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TreeCompilerPass());
    }
}
