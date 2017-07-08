<?php

namespace MakinaCorpus\Ucms\Contrib\DependencyInjection;

use MakinaCorpus\Ucms\Site\Access;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Calista configuration structure
 */
class ContribConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ucms_contrib');

        $rootNode
            ->children()
                // Keys will be tab route part for Drupal menu
                ->arrayNode('admin_pages')
                    ->normalizeKeys(true)
                    ->prototype('array')
                        ->children()
                            // Tab definition
                            ->scalarNode('tab')
                                ->isRequired()
                                ->defaultValue('all')
                                ->validate()
                                ->ifNotInArray(['content', 'media', 'all'])
                                    ->thenInvalid('Invalid type type %s')
                                ->end()
                            ->end()
                            // Permission or access callback
                            ->scalarNode('access')->isRequired()->defaultValue(Access::PERM_SITE_DASHBOARD_ACCESS)->end()
                            // Page and tab title
                            ->scalarNode('title')->isRequired()->end()
                            // Arbitrary base query filters
                            ->variableNode('base_query')->end()
                        ->end() // children
                    ->end() // prototype
                ->end() // pages
            ->end() // children
        ;

        return $treeBuilder;
    }
}
