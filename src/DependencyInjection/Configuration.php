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

        /** @phpstan-ignore-next-line */
        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('exception_listener')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                            ->info('Enable/disable API exception listener for error handling')
                        ->end()
                        ->booleanNode('debug_mode')
                            ->defaultFalse()
                            ->info('When enabled, shows detailed error messages instead of generic ones')
                        ->end()
                    ->end()
                ->end()
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
                ->arrayNode('providers')
                    ->addDefaultsIfNotSet()
                    ->info('Configuration for request providers')
                    ->children()
                        ->arrayNode('list_data_request')
                            ->useAttributeAsKey('name')
                            ->info('Additional list data request providers')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('class')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                        ->info('The provider class that implements ListDataRequestProviderInterface')
                                        ->validate()
                                            ->ifString()
                                            ->then(function ($v) {
                                                if (!class_exists($v)) {
                                                    throw new \InvalidArgumentException(sprintf('Provider class "%s" does not exist', $v));
                                                }
                                                if (!is_subclass_of($v, 'Freema\ReactAdminApiBundle\Request\Provider\List\ListDataRequestProviderInterface')) {
                                                    throw new \InvalidArgumentException(sprintf('Provider class "%s" must implement ListDataRequestProviderInterface', $v));
                                                }

                                                return $v;
                                            })
                                        ->end()
                                    ->end()
                                    ->integerNode('priority')
                                        ->defaultValue(0)
                                        ->info('Priority of the provider (higher = checked first)')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('data_provider')
                    ->addDefaultsIfNotSet()
                    ->info('Data provider configuration')
                    ->children()
                        ->enumNode('type')
                            ->values(['custom', 'simple_rest'])
                            ->defaultValue('custom')
                            ->info('Type of data provider to use (custom = default bundle format, simple_rest = ra-data-simple-rest compatible)')
                        ->end()
                        ->arrayNode('options')
                            ->info('Provider-specific options')
                            ->prototype('variable')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
