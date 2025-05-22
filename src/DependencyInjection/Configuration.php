<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('react_admin_api');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('routing')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('prefix')
                            ->defaultValue('/api')
                            ->info('Base prefix for all API routes')
                        ->end()
                        ->booleanNode('load_routes')
                            ->defaultTrue()
                            ->info('Whether to load the bundle routes automatically')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('resources')
                    ->useAttributeAsKey('path')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('dto_class')
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->info('The DTO class for this resource that implements DtoInterface')
                            ->end()
                            ->scalarNode('repository')
                                ->defaultNull()
                                ->info('Optional custom repository service ID')
                            ->end()
                            ->arrayNode('operations')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->booleanNode('list')->defaultTrue()->end()
                                    ->booleanNode('get')->defaultTrue()->end()
                                    ->booleanNode('create')->defaultTrue()->end()
                                    ->booleanNode('update')->defaultTrue()->end()
                                    ->booleanNode('delete')->defaultTrue()->end()
                                    ->booleanNode('delete_many')->defaultTrue()->end()
                                ->end()
                            ->end()
                            ->arrayNode('related_resources')
                                ->useAttributeAsKey('path')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('dto_class')
                                            ->isRequired()
                                            ->cannotBeEmpty()
                                            ->info('The DTO class for the related resource')
                                        ->end()
                                        ->scalarNode('repository')
                                            ->defaultNull()
                                            ->info('Optional custom repository service ID for the related resource')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('repository_manager')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('service')
                            ->defaultValue('doctrine.orm.entity_manager')
                            ->info('Service ID for the repository manager (always Doctrine entity manager)')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}