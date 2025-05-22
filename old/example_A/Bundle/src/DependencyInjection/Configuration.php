<?php

declare(strict_types=1);

namespace VLM\TaskWorkerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('vlm_task_worker');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->arrayNode('tasks')
            ->useAttributeAsKey('name')
            ->arrayPrototype()
            ->children()
            ->scalarNode('class')->isRequired()->end()
            ->scalarNode('schedule')->isRequired()->end()
            ->integerNode('usersCount')->defaultNull()->end()
            ->integerNode('limit')->defaultNull()->end()
            ->integerNode('batchSize')->defaultNull()->end()
            ->end()
            ->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}
