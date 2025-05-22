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
                ->arrayNode('resources')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('path')
                    ->info('Resource configuration mapping resource paths to DTOs')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('dto_class')
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->info('The DTO class for this resource that extends AdminApiDto and implements DtoInterface')
                                ->validate()
                                    ->ifString()
                                    ->then(function ($v) {
                                        if (!class_exists($v)) {
                                            throw new \InvalidArgumentException(sprintf('DTO class "%s" does not exist', $v));
                                        }
                                        if (!is_subclass_of($v, 'Freema\ReactAdminApiBundle\Interface\DtoInterface')) {
                                            throw new \InvalidArgumentException(sprintf('DTO class "%s" must implement DtoInterface', $v));
                                        }
                                        return $v;
                                    })
                                ->end()
                            ->end()
                            ->arrayNode('related_resources')
                                ->useAttributeAsKey('path')
                                ->info('Configuration for related resources accessible via /{resource}/{id}/{related_resource}')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('dto_class')
                                            ->isRequired()
                                            ->cannotBeEmpty()
                                            ->info('The DTO class for the related resource')
                                            ->validate()
                                                ->ifString()
                                                ->then(function ($v) {
                                                    if (!class_exists($v)) {
                                                        throw new \InvalidArgumentException(sprintf('Related DTO class "%s" does not exist', $v));
                                                    }
                                                    if (!is_subclass_of($v, 'Freema\ReactAdminApiBundle\Interface\DtoInterface')) {
                                                        throw new \InvalidArgumentException(sprintf('Related DTO class "%s" must implement DtoInterface', $v));
                                                    }
                                                    return $v;
                                                })
                                            ->end()
                                        ->end()
                                        ->scalarNode('relationship_method')
                                            ->defaultNull()
                                            ->info('Method name on parent entity to get related entities (e.g. "getPosts")')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}