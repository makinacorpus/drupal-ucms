<?php

namespace MakinaCorpus\Ucms\Contrib\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Usable extension for both Symfony, Drupal and may be other dependency
 * injection based environments.
 */
class ContribExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        // This will be the config key
        return 'ucms_contrib';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);

        // From the configured pages, build services
        foreach ($configs as $config) {
            // Do not process everything at once, it will erase array keys
            // of all pages definitions except those from the very first config
            // file, and break all our services identifiers
            $config = $this->processConfiguration($configuration, [$config]);

            if (empty($config['admin_pages'])) {
                // If any (one or more) module, extension or app config itself
                // provided pages, we drop defaults, but in case none did, we
                // must provide something to the user.
                // All strings will be translated automatically at runtime.
                $config['admin_pages'] = [
                    'mine' => [
                        'title'       => "My content",
                        'access'      => 'access ucms content overview',
                        'base_query'  => [],
                    ],
                    'local' => [
                        'title'       => "Local",
                        'access'      => 'access ucms content overview',
                        'base_query'  => [
                            'is_global' => 0,
                        ],
                    ],
                    'global' => [
                        'title'       => "Global",
                        'access'      => 'content manage global',
                        'base_query'  => [
                            'is_global'     => 1,
                            'is_corporate'  => 0,
                        ],
                    ],
                    'flagged' => [
                        'title'       => "Flagged",
                        'access'      => 'content manage global',
                        'base_query'  => [
                            'is_flagged' => 1
                        ],
                    ],
                    'starred' => [
                        'title'       => "Starred",
                        'access'      => 'content manage global',
                        'base_query'  => [
                            'is_starred' => 1
                        ],
                    ],
                ];
            }

            // This will be re-used as is without any transformation into
            // the ucms_contrib_menu() implementation to build node admin
            // pages. Configuration implementation, in theory, validated
            // and normalized the input so it's safe to proceed.
            $container->setParameter('ucms_contrib.admin_pages', $config['admin_pages']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new ContribConfiguration();
    }
}
