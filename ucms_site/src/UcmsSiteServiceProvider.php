<?php

namespace Drupal\ucms_site;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use MakinaCorpus\Ucms\Site\DependencyInjection\DrupalOverridesPass;

class UcmsSiteServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerBuilder $container)
    {
        $container->addCompilerPass(new DrupalOverridesPass());
    }
}
